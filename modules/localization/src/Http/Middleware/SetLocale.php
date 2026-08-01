<?php

namespace Liberu\Foundation\Localization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Liberu\Foundation\Localization\Context\LocaleContext;
use Liberu\Foundation\Localization\Context\LocaleResolver;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    private readonly LocaleResolver $resolver;

    public function __construct(?LocaleResolver $resolver = null)
    {
        $this->resolver = $resolver ?? new LocaleResolver();
    }

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->resolver->resolve($request);
        App::setLocale($context->locale);
        $request->attributes->set(LocaleContext::class, $context);

        if ($request->has('locale') && $request->input('locale') === $context->locale) {
            Session::put('locale', $context->locale);
        }

        return $next($request);
    }
}
