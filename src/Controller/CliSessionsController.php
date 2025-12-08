<?php

namespace Universal\HttpClientProfiler\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Universal\HttpClientProfiler\Session\SessionReader;

class CliSessionsController extends AbstractController
{
    public function __construct(private readonly SessionReader $sessionReader)
    {
    }

    #[Route(path: '/_profiler/cli-sessions', name: 'universal_http_client_profiler.cli_sessions', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('@UniversalHttpClientProfiler/Profiler/cli_sessions.html.twig', [
            'sessions' => $this->sessionReader->listSessions(),
        ]);
    }
}
