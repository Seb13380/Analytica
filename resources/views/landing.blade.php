<x-guest-layout>
    <div class="w-full sm:max-w-3xl mt-6 px-6 py-10 bg-white shadow-md overflow-hidden sm:rounded-lg">
        <div class="text-center">
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">Analytica</h1>
            <p class="mt-3 text-sm sm:text-base text-gray-600">
                Analyse de relevés et génération de rapports neutres (MVP).
            </p>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3">
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Créer un compte
            </a>

            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Se connecter
            </a>
        </div>

        <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div class="border rounded-md p-4">
                <div class="font-medium text-gray-900">Import</div>
                <div class="mt-1 text-gray-600">Upload relevés (CSV MVP) + détection doublons.</div>
            </div>
            <div class="border rounded-md p-4">
                <div class="font-medium text-gray-900">Analyse</div>
                <div class="mt-1 text-gray-600">Règles pondérées R1–R6 + score dossier.</div>
            </div>
            <div class="border rounded-md p-4">
                <div class="font-medium text-gray-900">Rapport</div>
                <div class="mt-1 text-gray-600">PDF neutre, chiffré au stockage.</div>
            </div>
        </div>
    </div>
</x-guest-layout>
