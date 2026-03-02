<x-guest-layout>
    <div class="mb-5 text-sm" style="color:#5C5449;line-height:1.75;">
        Merci pour votre inscription ! Avant de commencer, veuillez vérifier votre adresse e-mail en cliquant sur le lien que nous venons de vous envoyer. Si vous n'avez pas reçu l'e-mail, nous pouvons vous en renvoyer un.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm" style="color:#9B7A2A;">
            Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                Renvoyer l'e-mail de vérification
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="underline text-sm rounded-md focus:outline-none" style="color:#5C5449;">
                Déconnexion
            </button>
        </form>
    </div>
</x-guest-layout>
