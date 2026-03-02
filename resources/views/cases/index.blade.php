<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 style="font-family:'Cormorant Garamond',serif;font-weight:400;font-size:1.5rem;color:#1C1916;">
                Dossiers
            </h2>
            <a href="{{ route('cases.create') }}" class="btn-luxury-primary" style="padding:10px 28px;font-size:0.65rem;text-decoration:none;">
                + Nouveau dossier
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="luxury-card overflow-hidden">
                <div style="height:2px;background:linear-gradient(90deg,#9B7A2A,#E0C278,#9B7A2A);"></div>
                <div class="p-6" style="color:#1C1916;">
                    @if (session('status'))
                        <div class="mb-4 text-sm" style="color:#9B7A2A;">{{ session('status') }}</div>
                    @endif

                    @if ($cases->count() === 0)
                        <p class="text-sm text-gray-600">Aucun dossier pour l’instant.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr style="border-bottom:1px solid #EDE4D0;">
                                        <th class="py-3 pr-4 text-left" style="font-size:0.6rem;letter-spacing:0.2em;text-transform:uppercase;color:#C9A84C;font-weight:600;">Titre</th>
                                        <th class="py-3 pr-4 text-left" style="font-size:0.6rem;letter-spacing:0.2em;text-transform:uppercase;color:#C9A84C;font-weight:600;">Statut</th>
                                        <th class="py-3 pr-4 text-left" style="font-size:0.6rem;letter-spacing:0.2em;text-transform:uppercase;color:#C9A84C;font-weight:600;">Score</th>
                                        <th class="py-3 pr-4 text-left" style="font-size:0.6rem;letter-spacing:0.2em;text-transform:uppercase;color:#C9A84C;font-weight:600;">Créé le</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cases as $case)
                                        <tr style="border-bottom:1px solid #F7F2E8;transition:background 0.15s;" onmouseover="this.style.background='#F7F2E8';" onmouseout="this.style.background='transparent';">
                                            <td class="py-3 pr-4">
                                                <a style="color:#9B7A2A;font-weight:500;text-decoration:none;" onmouseover="this.style.color='#C9A84C';" onmouseout="this.style.color='#9B7A2A';" href="{{ route('cases.show', $case) }}">
                                                    {{ $case->title }}
                                                </a>
                                            </td>
                                            <td class="py-3 pr-4" style="color:#5C5449;">
                                                @php
                                                    $statuts = ['pending'=>'En attente','processing'=>'En cours','completed'=>'Terminé','failed'=>'Erreur'];
                                                @endphp
                                                {{ $statuts[$case->status] ?? $case->status }}
                                            </td>
                                            <td class="py-3 pr-4" style="color:#5C5449;">{{ $case->global_score ?? '—' }}</td>
                                            <td class="py-3 pr-4" style="color:#5C5449;">{{ $case->created_at->format('d/m/Y') }}</td>
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
