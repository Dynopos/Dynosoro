<?php

// Every external call goes through one client class, so it is easy to mock.
arch('only the API clients use the HTTP client')
    ->expect('Illuminate\Support\Facades\Http')
    ->toOnlyBeUsedIn([
        'App\Services\Ai\VertexClient',
        'App\Services\Publishing\GitHubClient',
        'App\Services\Publishing\IndexNowClient',
    ]);

// AiGateway checks AiBudget::assertCanSpend() and records ai_usage around every call (rule 4).
arch('Vertex is only called through AiGateway')
    ->expect('App\Services\Ai\VertexClient')
    ->toOnlyBeUsedIn('App\Services\Ai\AiGateway');

// Commits pass RepoGuard (rule 5).
// SiteAnalyst only reads the home page (getFile) for the business analysis.
arch('GitHub writes only go through SitePublisher')
    ->expect('App\Services\Publishing\GitHubClient')
    ->toOnlyBeUsedIn(['App\Services\Publishing\SitePublisher', 'App\Services\Publishing\SiteTemplates', 'App\Services\Sites\SiteService', 'App\Services\Content\SiteAnalyst']);

arch('Livewire components hold no client dependencies')
    ->expect('App\Livewire')
    ->not->toUse(['App\Services\Ai\VertexClient', 'App\Services\Publishing\GitHubClient', 'Illuminate\Support\Facades\Http']);

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
