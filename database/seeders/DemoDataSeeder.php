<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\StatutCv;
use App\Mail\StatutCandidatureMail;
use App\Models\Cv;
use App\Models\Entreprise;
use App\Models\MessageContact;
use App\Models\Notification;
use App\Models\Poste;
use App\Models\ResultatAnalyse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('cvs');
        $sampleSource = database_path('seeders/assets/sample-cv.pdf');
        $pdfSource = is_file($sampleSource)
            ? file_get_contents($sampleSource)
            : $this->contenuPdfDemoGenerique();

        $demoPassword = 'password';

        $admin = User::updateOrCreate(
            ['email' => 'admin@cvapp.test'],
            [
                'name' => 'Admin Principal',
                'password' => $demoPassword,
                'role' => Role::Admin,
                'entreprise_id' => null,
            ]
        );

        $techCorp = Entreprise::updateOrCreate(
            ['nom' => 'TechCorp'],
            [
                'description' => 'ESN spécialisée en développement web et cloud. Équipes agiles, projets Laravel/Vue, télétravail partiel possible.',
            ]
        );
        $dataSoft = Entreprise::updateOrCreate(
            ['nom' => 'DataSoft'],
            [
                'description' => 'Cabinet data & IA : analytics, BI, machine learning. Clients grands comptes et startups.',
            ]
        );

        $gerantTech = User::updateOrCreate(
            ['email' => 'gerant@techcorp.test'],
            [
                'name' => 'Luc Gérant TechCorp',
                'telephone' => '+33601020304',
                'password' => $demoPassword,
                'role' => Role::SuperAdmin,
                'admin_id' => $admin->id,
                'entreprise_id' => $techCorp->id,
                'est_actif' => true,
            ]
        );

        $gerantData = User::updateOrCreate(
            ['email' => 'gerant@datasoft.test'],
            [
                'name' => 'Nadia Gérant DataSoft',
                'telephone' => '+33605060708',
                'password' => $demoPassword,
                'role' => Role::SuperAdmin,
                'admin_id' => $admin->id,
                'entreprise_id' => $dataSoft->id,
                'est_actif' => true,
            ]
        );

        $rhTech = User::updateOrCreate(
            ['email' => 'rh@cvapp.test'],
            [
                'name' => 'Marie RH',
                'telephone' => '+33611111111',
                'password' => $demoPassword,
                'role' => Role::SousAdmin,
                'super_admin_id' => $gerantTech->id,
                'admin_id' => null,
                'entreprise_id' => $techCorp->id,
                'est_actif' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'rh2@cvapp.test'],
            [
                'name' => 'Paul RH',
                'telephone' => '+33622222222',
                'password' => $demoPassword,
                'role' => Role::SousAdmin,
                'super_admin_id' => $gerantTech->id,
                'entreprise_id' => $techCorp->id,
                'est_actif' => true,
            ]
        );

        $rhData = User::updateOrCreate(
            ['email' => 'rh3@cvapp.test'],
            [
                'name' => 'Sophie RH',
                'telephone' => '+33633333333',
                'password' => $demoPassword,
                'role' => Role::SousAdmin,
                'super_admin_id' => $gerantData->id,
                'entreprise_id' => $dataSoft->id,
                'est_actif' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'candidat@cvapp.test'],
            [
                'name' => 'Jean Candidat',
                'password' => $demoPassword,
                'role' => Role::Candidat,
                'entreprise_id' => null,
            ]
        );

        User::where('role', Role::SousAdmin)
            ->whereNull('entreprise_id')
            ->update(['entreprise_id' => $techCorp->id]);

        $posteLaravel = Poste::updateOrCreate(
            ['entreprise_id' => $techCorp->id, 'titre' => 'Développeur Laravel'],
            [
                'user_id' => $rhTech->id,
                'description' => 'Backend PHP/Laravel, API REST, PostgreSQL',
                'est_ouvert' => true,
                'date_creation' => now()->subMonths(2),
            ]
        );

        $posteDevOps = Poste::updateOrCreate(
            ['entreprise_id' => $techCorp->id, 'titre' => 'Ingénieur DevOps'],
            [
                'user_id' => $rhTech->id,
                'description' => 'Docker, CI/CD, Linux, AWS',
                'est_ouvert' => true,
                'date_creation' => now()->subMonth(),
            ]
        );

        $posteFrontend = Poste::updateOrCreate(
            ['entreprise_id' => $techCorp->id, 'titre' => 'Développeur Frontend Vue'],
            [
                'user_id' => User::where('email', 'rh2@cvapp.test')->first()->id,
                'description' => 'Vue.js, JavaScript, SCSS, Inertia',
                'est_ouvert' => true,
                'date_creation' => now()->subWeeks(2),
            ]
        );

        $posteData = Poste::updateOrCreate(
            ['entreprise_id' => $dataSoft->id, 'titre' => 'Data Analyst'],
            [
                'user_id' => $rhData->id,
                'description' => 'SQL, Python, Power BI, Excel',
                'est_ouvert' => true,
                'date_creation' => now()->subMonths(3),
            ]
        );

        $posteMl = Poste::updateOrCreate(
            ['entreprise_id' => $dataSoft->id, 'titre' => 'Ingénieur ML'],
            [
                'user_id' => $rhData->id,
                'description' => 'Python, TensorFlow, scikit-learn, NLP',
                'est_ouvert' => true,
                'date_creation' => now()->subWeek(),
            ]
        );

        $candidat = User::where('email', 'candidat@cvapp.test')->first();

        $cvsDemo = [
            [
                'nom' => 'Amine Benali',
                'email' => 'amine.benali@email.test',
                'poste_id' => $posteLaravel->id,
                'entreprise_id' => $techCorp->id,
                'texte' => 'Développeur full-stack avec 4 ans d\'expérience. Laravel, PHP 8, MySQL, PostgreSQL, API REST, Git, Docker. Projets e-commerce et SaaS.',
                'statut' => StatutCv::CvRecu,
            ],
            [
                'nom' => 'Sara Khaldi',
                'email' => 'sara.khaldi@email.test',
                'poste_id' => $posteLaravel->id,
                'entreprise_id' => $techCorp->id,
                'texte' => 'Ingénieure logiciel Java et Spring Boot. Connaissance de PHP et Symfony. SQL, Agile, tests unitaires.',
                'statut' => StatutCv::CvRecu,
            ],
            [
                'nom' => 'Youssef Amrani',
                'email' => 'youssef.amrani@email.test',
                'poste_id' => $posteLaravel->id,
                'entreprise_id' => $techCorp->id,
                'texte' => 'Expert Laravel et Vue.js. Inertia.js, Pinia, Tailwind, PostgreSQL, Redis, queues Laravel, déploiement Vapor.',
                'statut' => StatutCv::CvRecu,
            ],
            [
                'nom' => 'Lina Mansouri',
                'email' => 'lina.mansouri@email.test',
                'poste_id' => $posteDevOps->id,
                'entreprise_id' => $techCorp->id,
                'texte' => 'DevOps : Docker, Kubernetes, GitLab CI, Jenkins, Linux, Nginx, monitoring Prometheus, AWS EC2 et S3.',
                'statut' => StatutCv::CvRecu,
            ],
            [
                'nom' => 'Karim El Fassi',
                'email' => 'karim.elfassi@email.test',
                'poste_id' => $posteDevOps->id,
                'entreprise_id' => $techCorp->id,
                'texte' => 'Administrateur système Windows Server, Active Directory. Scripting PowerShell basique.',
                'statut' => StatutCv::CvRecu,
            ],
            [
                'nom' => 'Nadia Berrada',
                'email' => 'nadia.berrada@email.test',
                'poste_id' => $posteFrontend->id,
                'entreprise_id' => $techCorp->id,
                'texte' => 'Développeuse frontend Vue 3, Composition API, Pinia, Vite, SCSS, accessibilité, tests Vitest.',
                'statut' => StatutCv::EnCoursAnalyse,
            ],
            [
                'nom' => 'Omar Tazi',
                'email' => 'omar.tazi@email.test',
                'poste_id' => $posteFrontend->id,
                'entreprise_id' => $techCorp->id,
                'texte' => 'React, Next.js, TypeScript, GraphQL. Expérience limitée en Vue.',
                'statut' => StatutCv::CvRecu,
            ],
            [
                'nom' => 'Fatima Zahra',
                'email' => 'fatima.zahra@email.test',
                'poste_id' => $posteData->id,
                'entreprise_id' => $dataSoft->id,
                'texte' => 'Analyste data : SQL avancé, Python, pandas, matplotlib, Power BI, tableaux de bord KPI, ETL.',
                'statut' => StatutCv::CvRecu,
            ],
            [
                'nom' => 'Hassan Idrissi',
                'email' => 'hassan.idrissi@email.test',
                'poste_id' => $posteData->id,
                'entreprise_id' => $dataSoft->id,
                'texte' => 'Comptable senior, Excel, SAP. Pas de compétences en programmation data.',
                'statut' => StatutCv::CvRecu,
            ],
            [
                'nom' => 'Jean Candidat',
                'email' => 'candidat@cvapp.test',
                'poste_id' => $posteMl->id,
                'entreprise_id' => $dataSoft->id,
                'texte' => 'Étudiant en IA : Python, scikit-learn, TensorFlow, NLP, classification, réseaux de neurones.',
                'statut' => StatutCv::CvRecu,
                'user_id' => $candidat?->id,
            ],
            [
                'nom' => 'Aya Cherkaoui',
                'email' => 'aya.cherkaoui@email.test',
                'poste_id' => $posteMl->id,
                'entreprise_id' => $dataSoft->id,
                'texte' => 'Chercheuse ML : Python, PyTorch, deep learning, computer vision, publication scientifique.',
                'statut' => StatutCv::Valide,
            ],
            [
                'nom' => 'Salma Récente',
                'email' => 'salma.recente@email.test',
                'poste_id' => $posteLaravel->id,
                'entreprise_id' => $techCorp->id,
                'texte' => 'Développeuse Laravel, Vue, API REST. Candidature refusée récemment — éligible réanalyse (< 30 j).',
                'statut' => StatutCv::NonValide,
                'date_depot' => now()->subDays(12),
            ],
            [
                'nom' => 'Mehdi Ancien',
                'email' => 'mehdi.ancien@email.test',
                'poste_id' => $posteLaravel->id,
                'entreprise_id' => $techCorp->id,
                'texte' => 'Développeur PHP junior. Dépôt il y a plus de 30 jours — non éligible à la réanalyse « non validés ».',
                'statut' => StatutCv::NonValide,
                'date_depot' => now()->subDays(45),
            ],
        ];

        foreach ($cvsDemo as $i => $data) {
            $fichierUrl = $this->publierPdfDemo($data['email'], $pdfSource);

            Cv::updateOrCreate(
                ['email_candidat' => $data['email'], 'poste_id' => $data['poste_id']],
                [
                    'entreprise_id' => $data['entreprise_id'],
                    'user_id' => $data['user_id'] ?? null,
                    'nom_candidat' => $data['nom'],
                    'fichier_url' => $fichierUrl,
                    'taille_fichier' => round(strlen($pdfSource) / 1024 / 1024, 2) ?: 0.15,
                    'format_fichier' => 'pdf',
                    'texte_extrait' => $data['texte'],
                    'statut' => $data['statut'],
                    'date_depot' => $data['date_depot'] ?? now()->subDays(3 + $i),
                    'modifiable_jusqu' => ($data['statut'] ?? StatutCv::CvRecu) === StatutCv::CvRecu
                        ? ($data['date_depot'] ?? now())->copy()->addDay()
                        : ($data['date_depot'] ?? now())->copy()->subHour(),
                    'importe_par_rh' => $data['importe_par_rh'] ?? false,
                ]
            );
        }

        if (! Cv::where('importe_par_rh', true)->where('poste_id', $posteLaravel->id)->exists()) {
            Cv::create([
                'poste_id' => $posteLaravel->id,
                'entreprise_id' => $techCorp->id,
                'user_id' => null,
                'nom_candidat' => '',
                'email_candidat' => '',
                'fichier_url' => $this->publierPdfDemo('import-rh-laravel', $pdfSource),
                'taille_fichier' => round(strlen($pdfSource) / 1024 / 1024, 2) ?: 0.15,
                'format_fichier' => 'pdf',
                'texte_extrait' => 'CV importé par RH — Laravel PHP Vue PostgreSQL API REST Git Docker.',
                'statut' => StatutCv::CvRecu,
                'date_depot' => now()->subHours(2),
                'modifiable_jusqu' => null,
                'importe_par_rh' => true,
            ]);
        }

        MessageContact::query()->delete();
        MessageContact::create([
            'nom' => 'Claire Dupont',
            'email' => 'claire.dupont@acme.test',
            'telephone' => '0611223344',
            'entreprise' => 'Acme SA',
            'message' => 'Bonjour, nous souhaitons une démo pour 15 postes ouverts.',
            'lu' => false,
        ]);
        MessageContact::create([
            'nom' => 'Marc Leroy',
            'email' => 'marc.leroy@startup.test',
            'telephone' => '0699887766',
            'entreprise' => 'StartupLab',
            'message' => 'Question sur l\'hébergement et la sécurité des données candidats.',
            'lu' => true,
        ]);

        ResultatAnalyse::query()
            ->whereHas('cv', fn ($q) => $q->where('statut', StatutCv::CvRecu))
            ->delete();

        if ($candidat) {
            $cvCandidat = Cv::where('user_id', $candidat->id)->first();
            if ($cvCandidat) {
                $demoNotifs = [
                    [StatutCv::CvRecu, 2],
                    [StatutCv::EnCoursAnalyse, 1],
                ];
                foreach ($demoNotifs as [$statut, $jours]) {
                    Notification::updateOrCreate(
                        [
                            'user_id' => $candidat->id,
                            'cv_id' => $cvCandidat->id,
                            'statut_au_moment' => $statut->value,
                        ],
                        [
                            'message' => StatutCandidatureMail::messageCourt($statut),
                            'lu' => false,
                            'date_envoi' => now()->subDays($jours),
                        ]
                    );
                }
            }
        }

        $this->command?->info('Données démo créées : '.Cv::count().' CVs, '.Poste::count().' postes, '.MessageContact::count().' messages contact.');
        $this->command?->info('Mot de passe démo pour tous les comptes : password');
        $this->command?->info('Admin plateforme : admin@cvapp.test → '.config('cvanalyzer.admin_login_path', 'acces-admin-plateforme'));
        $this->command?->info('Gérant TechCorp : gerant@techcorp.test → '.config('cvanalyzer.gerant_login_path', 'acces-gerant-entreprise'));
        $this->command?->info('Gérant DataSoft : gerant@datasoft.test');
        $this->command?->info('RH TechCorp : rh@cvapp.test / rh2@cvapp.test');
        $this->command?->info('RH DataSoft : rh3@cvapp.test');
        $this->command?->info('Candidat : candidat@cvapp.test → /login');
        $this->command?->info('CV importé RH (sans e-mail) : dossier TechCorp / Développeur Laravel');
        if (! is_file(database_path('seeders/assets/sample-cv.pdf'))) {
            $this->command?->warn('Astuce : placez votre CV dans database/seeders/assets/sample-cv.pdf puis relancez le seed.');
        }
    }

    private function publierPdfDemo(string $identifiant, string $contenu): string
    {
        $chemin = 'cvs/demo-'.Str::slug($identifiant).'.pdf';
        Storage::disk('public')->put($chemin, $contenu);

        return $chemin;
    }

    private function contenuPdfDemoGenerique(): string
    {
        $texte = 'CV demo Laravel PHP Vue PostgreSQL Docker Git API REST Inertia';
        $stream = "BT /F1 11 Tf 50 700 Td ({$texte}) Tj ET";
        $len = strlen($stream);

        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n";
        $pdf .= "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n";
        $pdf .= "3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n";
        $pdf .= "4 0 obj<</Length {$len}>>stream\n{$stream}\nendstream\nendobj\n";
        $pdf .= "5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n";
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        $pdf .= "trailer<</Size 6/Root 1 0 R>>\nstartxref\n0\n%%EOF";

        return $pdf;
    }
}
