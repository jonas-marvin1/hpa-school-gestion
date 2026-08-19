{{--
    Enregistreur vocal intégré au formulaire de rendu.

    L'apprenant s'enregistre, se réécoute, recommence s'il le souhaite, puis
    envoie — sans quitter l'application ni passer par un dictaphone externe.

    Deux garde-fous :
      - le micro exige une connexion sécurisée (https) ; sur un navigateur ou
        un contexte qui ne le permet pas, on bascule automatiquement sur le
        dépôt de fichier plutôt que d'afficher un outil inutilisable ;
      - le fichier n'est constitué qu'au moment de l'envoi, à partir de
        l'enregistrement validé par l'apprenant.
--}}
<div x-data="enregistreurAudio()" x-init="init()" class="space-y-4">

    {{-- Champ réellement soumis. Alimenté par le micro ou par le dépôt manuel. --}}
    <input type="file" name="file" x-ref="champFichier" accept="audio/*" class="hidden">

    {{-- ————— Enregistrement ————— --}}
    <div x-show="microDisponible" x-cloak class="rounded-lg border border-gray-200 p-4">
        <div class="flex flex-wrap items-center gap-3">

            <button type="button" x-show="etat === 'attente'" @click="demarrer()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">
                <span class="h-2.5 w-2.5 rounded-full bg-white"></span>
                Démarrer l'enregistrement
            </button>

            <button type="button" x-show="etat === 'enregistrement'" @click="arreter()" x-cloak
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 text-white rounded-md text-sm font-medium hover:bg-gray-900">
                <span class="h-2.5 w-2.5 bg-white"></span>
                Arrêter
            </button>

            <div x-show="etat === 'enregistrement'" x-cloak class="flex items-center gap-2 text-sm text-red-700">
                <span class="h-2.5 w-2.5 rounded-full bg-red-600 animate-pulse"></span>
                <span class="font-mono" x-text="duree"></span>
                <span class="text-gray-500">— parlez, puis arrêtez</span>
            </div>

            <button type="button" x-show="etat === 'termine'" @click="recommencer()" x-cloak
                    class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                Recommencer
            </button>

            <span x-show="etat === 'termine'" x-cloak class="text-sm text-green-700">
                Enregistrement de <span class="font-mono" x-text="duree"></span> prêt
            </span>
        </div>

        {{-- Réécoute avant envoi : l'apprenant juge lui-même de sa prestation. --}}
        <div x-show="etat === 'termine'" x-cloak class="mt-4">
            <p class="text-xs font-medium text-gray-700 mb-1">Réécoutez avant d'envoyer :</p>
            <audio x-ref="lecteur" controls class="w-full"></audio>
        </div>

        <p x-show="erreur" x-cloak class="mt-3 text-sm text-red-700" x-text="erreur"></p>
    </div>

    {{-- ————— Repli : dépôt d'un fichier ————— --}}
    <div class="rounded-lg border border-gray-200 p-4">
        <p class="text-sm font-medium text-gray-700 mb-1"
           x-text="microDisponible ? 'Ou déposez un fichier déjà enregistré' : 'Déposez votre enregistrement'"></p>

        <p x-show="!microDisponible" x-cloak class="text-xs text-amber-700 mb-2">
            L'enregistrement direct n'est pas disponible sur ce navigateur. Utilisez le dictaphone
            de votre téléphone, puis déposez le fichier ici.
        </p>

        <input type="file" accept="audio/*" @change="fichierChoisi($event)"
               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">

        <p class="mt-2 text-xs text-gray-500">
            Formats acceptés : MP3, M4A, WAV, OGG, WebM &middot; 25 Mo maximum.
        </p>

        <div x-show="apercuFichier" x-cloak class="mt-3">
            <p class="text-xs font-medium text-gray-700 mb-1">Écoutez avant d'envoyer :</p>
            <audio x-ref="lecteurFichier" controls class="w-full"></audio>
        </div>
    </div>

    <p class="text-sm" :class="pret ? 'text-green-700' : 'text-gray-500'">
        <span x-show="pret" x-cloak>Votre enregistrement est prêt à être envoyé.</span>
        <span x-show="!pret">Enregistrez-vous ou déposez un fichier pour pouvoir envoyer.</span>
    </p>
</div>

<script>
    function enregistreurAudio() {
        return {
            microDisponible: false,
            etat: 'attente',          // attente | enregistrement | termine
            erreur: '',
            duree: '00:00',
            pret: false,
            apercuFichier: false,

            enregistreur: null,
            morceaux: [],
            flux: null,
            debut: null,
            minuterie: null,

            init() {
                // getUserMedia n'existe qu'en contexte sécurisé (https ou
                // localhost). Inutile d'afficher un bouton qui échouerait.
                this.microDisponible = !!(navigator.mediaDevices
                    && navigator.mediaDevices.getUserMedia
                    && window.MediaRecorder);
            },

            async demarrer() {
                this.erreur = '';

                try {
                    this.flux = await navigator.mediaDevices.getUserMedia({ audio: true });
                } catch (e) {
                    this.erreur = e.name === 'NotAllowedError'
                        ? "Accès au micro refusé. Autorisez-le dans votre navigateur, puis réessayez."
                        : "Micro introuvable. Vérifiez qu'un microphone est bien branché.";
                    return;
                }

                this.morceaux = [];
                this.enregistreur = new MediaRecorder(this.flux, this.formatSupporte());

                this.enregistreur.ondataavailable = e => {
                    if (e.data.size > 0) this.morceaux.push(e.data);
                };

                this.enregistreur.onstop = () => this.finaliser();

                this.enregistreur.start();
                this.etat = 'enregistrement';
                this.debut = Date.now();
                this.minuterie = setInterval(() => this.majDuree(), 250);
            },

            arreter() {
                if (this.enregistreur && this.enregistreur.state !== 'inactive') {
                    this.enregistreur.stop();
                }
                clearInterval(this.minuterie);
                // Libère le micro : sans cela le voyant reste allumé.
                if (this.flux) this.flux.getTracks().forEach(t => t.stop());
            },

            finaliser() {
                const type = this.enregistreur.mimeType || 'audio/webm';
                const blob = new Blob(this.morceaux, { type });

                this.$refs.lecteur.src = URL.createObjectURL(blob);

                // Le blob est transformé en fichier et posé dans le champ du
                // formulaire : l'envoi suit exactement le même chemin qu'un
                // dépôt manuel, validation serveur comprise.
                const extension = type.includes('ogg') ? 'ogg' : (type.includes('mp4') ? 'm4a' : 'webm');
                const fichier = new File([blob], 'enregistrement.' + extension, { type });

                const conteneur = new DataTransfer();
                conteneur.items.add(fichier);
                this.$refs.champFichier.files = conteneur.files;

                this.etat = 'termine';
                this.pret = true;
                this.apercuFichier = false;
            },

            recommencer() {
                this.etat = 'attente';
                this.pret = false;
                this.duree = '00:00';
                this.$refs.lecteur.removeAttribute('src');
                this.$refs.champFichier.value = '';
            },

            fichierChoisi(e) {
                const f = e.target.files && e.target.files[0];
                if (!f) { this.pret = false; this.apercuFichier = false; return; }

                const conteneur = new DataTransfer();
                conteneur.items.add(f);
                this.$refs.champFichier.files = conteneur.files;

                this.$refs.lecteurFichier.src = URL.createObjectURL(f);
                this.apercuFichier = true;
                this.pret = true;
                this.etat = 'attente';
            },

            majDuree() {
                const s = Math.floor((Date.now() - this.debut) / 1000);
                this.duree = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
            },

            // Chaque navigateur accepte des conteneurs différents : on retient
            // le premier disponible plutôt que d'en imposer un.
            formatSupporte() {
                const candidats = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];
                for (const t of candidats) {
                    if (MediaRecorder.isTypeSupported(t)) return { mimeType: t };
                }
                return {};
            },
        };
    }
</script>
<style>[x-cloak] { display: none !important; }</style>
