<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Créer un dossier') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('cases.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="title" :value="__('Titre')" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('title')" />
                        </div>

                        <div>
                            <x-input-label for="organization_id" :value="__('Organisation (optionnel)')" />
                            <select id="organization_id" name="organization_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
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

                        <div class="flex items-center gap-3">
                            <x-primary-button>
                                {{ __('Créer') }}
                            </x-primary-button>

                            <a class="text-sm text-gray-600 hover:underline" href="{{ route('cases.index') }}">
                                {{ __('Annuler') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
