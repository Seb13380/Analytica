<x-app-layout>
    <x-slot name="header">
        <h2 style="font-family:'Cormorant Garamond',serif;font-weight:400;font-size:1.5rem;color:#1C1916;">
            Créer un dossier
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden luxury-card">
                <div style="height:2px;background:linear-gradient(90deg,#9B7A2A,#E0C278,#9B7A2A);"></div>
                <div class="p-6" style="color:#1C1916;">
                    <form method="POST" action="{{ route('cases.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="title" :value="__('Titre')" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('title')" />
                        </div>

                        <div>
                            <x-input-label for="organization_id" value="Organisation (optionnel)" />
                            <select id="organization_id" name="organization_id" class="select-luxury mt-1">
                                <option value="">—</option>
                                @foreach ($organizations as $org)
                                    <option value="{{ $org->id }}" @selected(old('organization_id') == $org->id)>
                                        {{ $org->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('organization_id')" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="deceased_name" :value="__('Nom du défunt (optionnel)')" />
                                <x-text-input id="deceased_name" name="deceased_name" type="text" class="mt-1 block w-full" :value="old('deceased_name')" />
                                <x-input-error class="mt-2" :messages="$errors->get('deceased_name')" />
                            </div>

                            <div>
                                <x-input-label for="death_date" :value="__('Date de décès (optionnel)')" />
                                <x-text-input id="death_date" name="death_date" type="date" class="mt-1 block w-full" :value="old('death_date')" />
                                <x-input-error class="mt-2" :messages="$errors->get('death_date')" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="analysis_period_start" :value="__('Début période (optionnel)')" />
                                <x-text-input id="analysis_period_start" name="analysis_period_start" type="date" class="mt-1 block w-full" :value="old('analysis_period_start')" />
                                <x-input-error class="mt-2" :messages="$errors->get('analysis_period_start')" />
                            </div>

                            <div>
                                <x-input-label for="analysis_period_end" :value="__('Fin période (optionnel)')" />
                                <x-text-input id="analysis_period_end" name="analysis_period_end" type="date" class="mt-1 block w-full" :value="old('analysis_period_end')" />
                                <x-input-error class="mt-2" :messages="$errors->get('analysis_period_end')" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Créer</x-primary-button>
                            <a style="font-size:0.75rem;color:#5C5449;text-decoration:underline;" href="{{ route('cases.index') }}">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
