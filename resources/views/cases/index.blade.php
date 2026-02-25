<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dossiers') }}
            </h2>

            <a href="{{ route('cases.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Nouveau dossier') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('status'))
                        <div class="mb-4 text-sm text-green-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($cases->count() === 0)
                        <p class="text-sm text-gray-600">Aucun dossier pour l’instant.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-600">
                                        <th class="py-2 pr-4">Titre</th>
                                        <th class="py-2 pr-4">Statut</th>
                                        <th class="py-2 pr-4">Score</th>
                                        <th class="py-2 pr-4">Créé</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($cases as $case)
                                        <tr>
                                            <td class="py-3 pr-4">
                                                <a class="text-green-700 hover:underline" href="{{ route('cases.show', $case) }}">
                                                    {{ $case->title }}
                                                </a>
                                            </td>
                                            <td class="py-3 pr-4">{{ $case->status }}</td>
                                            <td class="py-3 pr-4">{{ $case->global_score ?? '—' }}</td>
                                            <td class="py-3 pr-4">{{ $case->created_at->format('Y-m-d') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $cases->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
