<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium" style="color:#1C1916;font-family:'Cormorant Garamond',serif;font-size:1.3rem;font-weight:400;">
            Supprimer le compte
        </h2>
        <p class="mt-1 text-sm" style="color:#5C5449;">Une fois votre compte supprimé, toutes ses ressources et données seront définitivement effacées. Avant de procéder, téléchargez toute information que vous souhaitez conserver.</p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Supprimer le compte</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium" style="color:#1C1916;">Confirmer la suppression du compte ?</h2>

            <p class="mt-2 text-sm" style="color:#5C5449;">
                Cette action est irréversible. Toutes vos données seront définitivement supprimées. Saisissez votre mot de passe pour confirmer.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Mot de passe" class="sr-only" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-3/4" placeholder="Mot de passe" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Annuler
                </x-secondary-button>
                <x-danger-button>
                    Supprimer définitivement
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
