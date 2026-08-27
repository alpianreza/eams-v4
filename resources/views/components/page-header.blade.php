@props([
    'variant' => 'card',
    'tone' => '',
    'eyebrow' => '',
    'eyebrowIcon' => '',
    'title' => '',
    'lead' => '',
    'leadHtml' => '',
    'backUrl' => '',
])

@php($variant = in_array($variant, ['card', 'plain', 'command'], true) ? $variant : 'card')

<header class="page-header page-header--{{ $variant }}" @if($tone !== '') data-tone="{{ $tone }}" @endif>
    <div class="page-header__content">
        @if(isset($media) && ! $media->isEmpty())
            <div class="page-header__media">{{ $media }}</div>
        @endif

        <div class="page-header__text">
            @if($backUrl !== '')
                <a href="{{ $backUrl }}" class="page-header__back">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali
                </a>
            @endif

            @if($eyebrow !== '')
                <p class="page-header__eyebrow">
                    @if($eyebrowIcon !== '')<i class="bi {{ $eyebrowIcon }}"></i>@endif
                    {{ $eyebrow }}
                </p>
            @endif

            <h1 class="page-header__title">{{ $title }}</h1>

            @if($leadHtml !== '')
                <p class="page-header__lead">{!! $leadHtml !!}</p>
            @elseif($lead !== '')
                <p class="page-header__lead">{{ $lead }}</p>
            @endif
        </div>
    </div>

    @if(isset($actions) && ! $actions->isEmpty())
        <div class="page-header__actions">{{ $actions }}</div>
    @endif
</header>
