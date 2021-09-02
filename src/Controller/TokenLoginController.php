<?php

namespace Contaobayern\ErtlBundle\Controller;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\FrontendUser;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\PageError403;
use Contao\MemberModel;
use Contao\PageModel;
use Contaobayern\ErtlBundle\Model\MemberTokenModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles the bundle's frontend login route.
 *
 * Code mostly analogous to
 * https://github.com/terminal42/contao-autoregistration/blob/main/src/EventListener/RegistrationListener.php
 *
 */
class TokenLoginController extends AbstractController
{
    private UserProviderInterface $userProvider;
    private TokenStorageInterface $tokenStorage;
    private EventDispatcherInterface $dispatcher;
    private UserCheckerInterface $userChecker;
    private AuthenticationSuccessHandlerInterface $authenticationSuccessHandler;
    private TokenChecker $tokenChecker;
    private LoggerInterface $logger;
    private RequestStack $requestStack;

    public function __construct(
        UserProviderInterface                 $userProvider,
        TokenStorageInterface                 $tokenStorage,
        EventDispatcherInterface              $dispatcher,
        UserCheckerInterface                  $userChecker,
        AuthenticationSuccessHandlerInterface $authenticationSuccessHandler,
        TokenChecker $tokenChecker,
        LoggerInterface $logger,
        RequestStack $requestStack
    ) {
        $this->userProvider = $userProvider;
        $this->tokenStorage = $tokenStorage;
        $this->dispatcher = $dispatcher;
        $this->userChecker = $userChecker;
        $this->authenticationSuccessHandler = $authenticationSuccessHandler;
        $this->tokenChecker = $tokenChecker;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
    }


    /**
     * Login per Token.
     */
    public function loginAction(Request $request, string $token, int $redirecttopagewithid = 0): Response
    {
        /** @var MemberTokenModel $tokenModel */
        $tokenModel = MemberTokenModel::findByToken($token);
        if (!$tokenModel) {
            $this->show401Page('Ungültiges Token');
        }

        // Token expired?
        if ($tokenModel->validuntil < time()) {
            // TODO (?): Member und MemberToken bearbeiten
            $this->show401Page('Abgelaufenes Token');
        }

        $memberModel = MemberModel::findById($tokenModel->pid);
        if (!$memberModel) {
            $this->show401Page('Kein Member zum Token');
        }

        if ($memberModel->disable) {
            $this->show403Page('Gültiges Token aber Member disabled');
        }

        if ($request->getHost() !== $tokenModel->domain) {
            $this->show403Page('Gültiges Token aber nicht für die angegebene Domain');
        }

        $this->loginUser($memberModel->email, $request); // we use the email as username

        if ($redirecttopagewithid > 0) {
            $pageModel = PageModel::findByPk($redirecttopagewithid);
            if ($pageModel) {
                throw new RedirectResponseException($pageModel->getAbsoluteUrl());
            }
        }
        throw new RedirectResponseException("/"); // fallback
    }

    private function loginUser(string $username, Request $request): void
    {
        // User bereits angemeldet?
        if ($this->tokenChecker->hasFrontendUser()) { // checks if a front end user is authenticated
            // Do not create too many log entries
            // $this->logger->log(LogLevel::INFO, 'User "' . $username . '" was already logged in', [
            //     'contao' => new  ContaoContext(__METHOD__, TL_ACCESS)
            // ]);
            return;
        }

        try {
            $user = $this->userProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $exception) {
            throw new PageNotFoundException('Sorry. Das hätte nicht passieren dürfen.');
        }

        if (!$user instanceof FrontendUser) {
            throw new AccessDeniedException('Not a frontend user');
        }

        try {
            $this->userChecker->checkPreAuth($user);
            $this->userChecker->checkPostAuth($user);
        } catch (AccountStatusException $e) {
            throw new AccessDeniedException('Authentication checks failed');
        }

        $usernamePasswordToken = new UsernamePasswordToken($user, null, 'frontend', $user->getRoles());

        $this->tokenStorage->setToken($usernamePasswordToken);
        $event = new InteractiveLoginEvent($request, $usernamePasswordToken);
        $this->dispatcher->dispatch($event);
        // Do not log here as we would have duplicate "user has logged in" entries due to the following onAuthenticationSuccess() call
        // $this->logger->log(LogLevel::INFO, 'User "' . $username . '" was logged in automatically', [
        //     'contao' => new  ContaoContext(__METHOD__, TL_ACCESS)
        // ]);

        // For the following see https://github.com/terminal42/contao-autoregistration/blob/5fbecf266a758eb1ae2a3ed70e9d666f4867788d/src/EventListener/RegistrationListener.php#L143
        $request->request->set('_target_path', base64_encode($request->getRequestUri()));
        $this->authenticationSuccessHandler->onAuthenticationSuccess($request, $usernamePasswordToken);
    }

    /**
     * https://developer.mozilla.org/en-US/docs/Web/HTTP/Status#client_error_responses
     *
     * 401 Unauthorized
     * Although the HTTP standard specifies "unauthorized", semantically this response means "unauthenticated". That is, the client must authenticate itself to get the requested response.
     *
     * 403 Forbidden
     * The client does not have access rights to the content; that is, it is unauthorized, so the server is refusing to give the requested resource. Unlike 401, the client's identity is known to the server.
     *
     * 404 Not Found
     * The server can not find the requested resource. In the browser, this means the URL is not recognized. In an API, this can also mean that the endpoint is valid but the resource itself does not exist. Servers may also send this response instead of 403 to hide the existence of a resource from an unauthorized client. This response code is probably the most famous one due to its frequent occurrence on the web.
     */

    protected function show404Page($message): void
    {
        throw new PageNotFoundException($message);
    }

    protected function show401Page($message): void
    {
        throw new AccessDeniedException($message);
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function show403Page($message): void
    {
        $handler = new PageError403(); // TODO (?): $objRootPage setzen -- sollte automatisch gehen
        $handler->generate();
        exit;
    }
}
