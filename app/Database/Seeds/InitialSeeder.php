<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialSeeder extends Seeder
{
    public function run()
    {
        // ============================================
        // Types de contrat de bail
        // ============================================
        $this->db->table('types_contrat')->insertBatch([
            [
                'code'                => 'habitation',
                'libelle'             => 'Bail d\'habitation',
                'texte_reference'     => 'Ordonnance n°62-100 du 1er octobre 1962',
                'taux_enregistrement' => 1.00,
                'preavis_mois'        => 3,
                'modele_html'         => '<h1>CONTRAT DE BAIL D\'HABITATION</h1><p>{{contenu_a_completer}}</p>',
            ],
            [
                'code'                => 'commercial',
                'libelle'             => 'Bail commercial',
                'texte_reference'     => 'Loi n°2015-037 du 8 décembre 2015',
                'taux_enregistrement' => 2.00,
                'preavis_mois'        => 6,
                'modele_html'         => '<h1>CONTRAT DE BAIL COMMERCIAL</h1><p>{{contenu_a_completer}}</p>',
            ],
            [
                'code'                => 'mixte',
                'libelle'             => 'Bail à usage mixte',
                'texte_reference'     => 'Loi n°2015-037 du 8 décembre 2015',
                'taux_enregistrement' => 2.00,
                'preavis_mois'        => 6,
                'modele_html'         => '<h1>CONTRAT DE BAIL À USAGE MIXTE</h1><p>{{contenu_a_completer}}</p>',
            ],
        ]);

        // ============================================
        // Règles du moteur LegalTech
        // ============================================
        $this->db->table('regles_legaltech')->insertBatch([
            [
                'code'            => 'CIN_FORMAT_INVALIDE',
                'libelle'         => 'Format de la CIN incorrect',
                'niveau'          => 'bloquant',
                'article_loi'     => 'Décret relatif à la CIN',
                'message_affiche' => 'Le numéro de CIN saisi ne respecte pas le format attendu (12 chiffres).',
            ],
            [
                'code'            => 'CIN_DISTRICT_INCONNU',
                'libelle'         => 'District CIN inexistant',
                'niveau'          => 'bloquant',
                'article_loi'     => 'Décret relatif à la CIN',
                'message_affiche' => 'Les 3 premiers chiffres de la CIN ne correspondent à aucun district malgache connu.',
            ],
            [
                'code'            => 'SUROCCUPATION',
                'libelle'         => 'Nombre d\'occupants excessif',
                'niveau'          => 'alerte',
                'article_loi'     => 'Code civil — jouissance paisible',
                'message_affiche' => 'Le nombre d\'occupants dépasse le seuil recommandé pour ce bien.',
            ],
            [
                'code'            => 'USAGE_INCOHERENT',
                'libelle'         => 'Usage déclaré incohérent avec l\'activité réelle',
                'niveau'          => 'bloquant',
                'article_loi'     => 'Ordonnance n°62-100 ; jurisprudence usage prédominant',
                'message_affiche' => 'L\'usage déclaré (habitation) est incohérent avec la profession/activité déclarée.',
            ],
            [
                'code'            => 'NIF_STAT_MANQUANT',
                'libelle'         => 'NIF/STAT requis pour usage commercial/mixte',
                'niveau'          => 'alerte',
                'article_loi'     => 'Code Général des Impôts',
                'message_affiche' => 'Le NIF et le STAT sont requis pour un usage commercial ou mixte. Le contrat reste sous condition suspensive de 30 jours.',
            ],
            [
                'code'            => 'CAPACITE_MINEUR',
                'libelle'         => 'Locataire mineur non émancipé sans représentant',
                'niveau'          => 'bloquant',
                'article_loi'     => 'Code civil — capacité',
                'message_affiche' => 'Le locataire déclaré est mineur : un représentant légal est requis pour conclure le bail.',
            ],
            [
                'code'            => 'CAPACITE_CONDAMNE',
                'libelle'         => 'Locataire condamné avec interdiction de droits civils',
                'niveau'          => 'alerte',
                'article_loi'     => 'Code pénal',
                'message_affiche' => 'La situation du locataire nécessite une vérification juridique avant la génération du contrat.',
            ],
            [
                'code'            => 'CAUTION_PLAFOND',
                'libelle'         => 'Dépôt de garantie supérieur à 2 mois de loyer',
                'niveau'          => 'bloquant',
                'article_loi'     => 'Ordonnance n°62-100',
                'message_affiche' => 'Le dépôt de garantie ne peut pas dépasser deux mois de loyer.',
            ],
            [
                'code'            => 'LOYER_PLAFOND',
                'libelle'         => 'Loyer supérieur au plafond légal (immeuble > 5 ans)',
                'niveau'          => 'alerte',
                'article_loi'     => 'Ordonnance n°62-100',
                'message_affiche' => 'Le loyer proposé dépasse le plafond calculé pour un immeuble de plus de 5 ans.',
            ],
        ]);

        // ============================================
        // Référentiel des districts CIN
        // (111 districts, source : liste officielle des codes postaux par district)
        // ============================================
        $this->db->table('districts_cin')->insertBatch([
            ['code_district' => '105', 'province' => 'Antananarivo', 'region' => 'Analamanga', 'district' => 'Ambohidratrimo'],
            ['code_district' => '106', 'province' => 'Antananarivo', 'region' => 'Analamanga', 'district' => 'Andramasina'],
            ['code_district' => '107', 'province' => 'Antananarivo', 'region' => 'Analamanga', 'district' => 'Anjozorobe'],
            ['code_district' => '108', 'province' => 'Antananarivo', 'region' => 'Analamanga', 'district' => 'Ankazobe'],
            ['code_district' => '101', 'province' => 'Antananarivo', 'region' => 'Analamanga', 'district' => 'Antananarivo'],
            ['code_district' => '103', 'province' => 'Antananarivo', 'region' => 'Analamanga', 'district' => 'Antananarivo Nord'],
            ['code_district' => '102', 'province' => 'Antananarivo', 'region' => 'Analamanga', 'district' => 'Antananarivo Sud'],
            ['code_district' => '116', 'province' => 'Antananarivo', 'region' => 'Analamanga', 'district' => 'Manjakandriana'],
            ['code_district' => '115', 'province' => 'Antananarivo', 'region' => 'Bongolava', 'district' => 'Fenoarivo Centre'],
            ['code_district' => '119', 'province' => 'Antananarivo', 'region' => 'Bongolava', 'district' => 'Tsiroanomandidy'],
            ['code_district' => '112', 'province' => 'Antananarivo', 'region' => 'Itasy', 'district' => 'Arivonimamo'],
            ['code_district' => '117', 'province' => 'Antananarivo', 'region' => 'Itasy', 'district' => 'Miarinarivo'],
            ['code_district' => '118', 'province' => 'Antananarivo', 'region' => 'Itasy', 'district' => 'Soavinandriana'],
            ['code_district' => '104', 'province' => 'Antananarivo', 'region' => 'Vakinankaratra', 'district' => 'Ambatolampy'],
            ['code_district' => '109', 'province' => 'Antananarivo', 'region' => 'Vakinankaratra', 'district' => 'Antanifotsy'],
            ['code_district' => '111', 'province' => 'Antananarivo', 'region' => 'Vakinankaratra', 'district' => 'Antsirabe Rural'],
            ['code_district' => '110', 'province' => 'Antananarivo', 'region' => 'Vakinankaratra', 'district' => 'Antsirabe Urban'],
            ['code_district' => '113', 'province' => 'Antananarivo', 'region' => 'Vakinankaratra', 'district' => 'Betafo'],
            ['code_district' => '114', 'province' => 'Antananarivo', 'region' => 'Vakinankaratra', 'district' => 'Faratsiho'],
            ['code_district' => '203', 'province' => 'Antsiranana', 'region' => 'Diana', 'district' => 'Ambanja'],
            ['code_district' => '204', 'province' => 'Antsiranana', 'region' => 'Diana', 'district' => 'Ambilobe'],
            ['code_district' => '202', 'province' => 'Antsiranana', 'region' => 'Diana', 'district' => 'Antsiranana Rural'],
            ['code_district' => '201', 'province' => 'Antsiranana', 'region' => 'Diana', 'district' => 'Antsiranana Urban'],
            ['code_district' => '207', 'province' => 'Antsiranana', 'region' => 'Diana', 'district' => 'Nosy Be'],
            ['code_district' => '205', 'province' => 'Antsiranana', 'region' => 'Sava', 'district' => 'Andapa'],
            ['code_district' => '206', 'province' => 'Antsiranana', 'region' => 'Sava', 'district' => 'Antalaha'],
            ['code_district' => '208', 'province' => 'Antsiranana', 'region' => 'Sava', 'district' => 'Sambava'],
            ['code_district' => '209', 'province' => 'Antsiranana', 'region' => 'Sava', 'district' => 'Vohimarina (Iharana)'],
            ['code_district' => '304', 'province' => 'Fianarantsoa', 'region' => 'Amoron\'i Mania', 'district' => 'Ambatofinandrahana'],
            ['code_district' => '306', 'province' => 'Fianarantsoa', 'region' => 'Amoron\'i Mania', 'district' => 'Ambositra'],
            ['code_district' => '308', 'province' => 'Fianarantsoa', 'region' => 'Amoron\'i Mania', 'district' => 'Fandriana'],
            ['code_district' => '323', 'province' => 'Fianarantsoa', 'region' => 'Amoron\'i Mania', 'district' => 'Manandriana'],
            ['code_district' => '307', 'province' => 'Fianarantsoa', 'region' => 'Atsimo-Atsinana', 'district' => 'Befotaka'],
            ['code_district' => '309', 'province' => 'Fianarantsoa', 'region' => 'Atsimo-Atsinana', 'district' => 'Farafangana'],
            ['code_district' => '318', 'province' => 'Fianarantsoa', 'region' => 'Atsimo-Atsinana', 'district' => 'Midongy Sud'],
            ['code_district' => '320', 'province' => 'Fianarantsoa', 'region' => 'Atsimo-Atsinana', 'district' => 'Vangaindrano'],
            ['code_district' => '322', 'province' => 'Fianarantsoa', 'region' => 'Atsimo-Atsinana', 'district' => 'Vondrozo'],
            ['code_district' => '303', 'province' => 'Fianarantsoa', 'region' => 'Haute Matsiatra', 'district' => 'Ambalavao'],
            ['code_district' => '305', 'province' => 'Fianarantsoa', 'region' => 'Haute Matsiatra', 'district' => 'Ambohimahasoa'],
            ['code_district' => '302', 'province' => 'Fianarantsoa', 'region' => 'Haute Matsiatra', 'district' => 'Fianarantsoa Rural'],
            ['code_district' => '301', 'province' => 'Fianarantsoa', 'region' => 'Haute Matsiatra', 'district' => 'Fianarantsoa Urban'],
            ['code_district' => '314', 'province' => 'Fianarantsoa', 'region' => 'Haute Matsiatra', 'district' => 'Ikalamavony'],
            ['code_district' => '311', 'province' => 'Fianarantsoa', 'region' => 'Ihorombe', 'district' => 'Iakora'],
            ['code_district' => '313', 'province' => 'Fianarantsoa', 'region' => 'Ihorombe', 'district' => 'Ihosy'],
            ['code_district' => '315', 'province' => 'Fianarantsoa', 'region' => 'Ihorombe', 'district' => 'Ivohibe'],
            ['code_district' => '312', 'province' => 'Fianarantsoa', 'region' => 'Vatovavy Fitovinany', 'district' => 'Ifanadiana'],
            ['code_district' => '310', 'province' => 'Fianarantsoa', 'region' => 'Vatovavy Fitovinany', 'district' => 'Ikongo'],
            ['code_district' => '316', 'province' => 'Fianarantsoa', 'region' => 'Vatovavy Fitovinany', 'district' => 'Manakara Sud'],
            ['code_district' => '317', 'province' => 'Fianarantsoa', 'region' => 'Vatovavy Fitovinany', 'district' => 'Mananjary'],
            ['code_district' => '319', 'province' => 'Fianarantsoa', 'region' => 'Vatovavy Fitovinany', 'district' => 'Nosy Varika'],
            ['code_district' => '321', 'province' => 'Fianarantsoa', 'region' => 'Vatovavy Fitovinany', 'district' => 'Vohipeno'],
            ['code_district' => '411', 'province' => 'Mahajanga', 'region' => 'Betsiboka', 'district' => 'Kandreho'],
            ['code_district' => '412', 'province' => 'Mahajanga', 'region' => 'Betsiboka', 'district' => 'Maevatanana'],
            ['code_district' => '421', 'province' => 'Mahajanga', 'region' => 'Betsiboka', 'district' => 'Tsaratanana'],
            ['code_district' => '403', 'province' => 'Mahajanga', 'region' => 'Boeny', 'district' => 'Ambato-Boina'],
            ['code_district' => '402', 'province' => 'Mahajanga', 'region' => 'Boeny', 'district' => 'Mahajanga Rural'],
            ['code_district' => '401', 'province' => 'Mahajanga', 'region' => 'Boeny', 'district' => 'Mahajanga Urban'],
            ['code_district' => '416', 'province' => 'Mahajanga', 'region' => 'Boeny', 'district' => 'Marovoay'],
            ['code_district' => '417', 'province' => 'Mahajanga', 'region' => 'Boeny', 'district' => 'Mitsinjo'],
            ['code_district' => '420', 'province' => 'Mahajanga', 'region' => 'Boeny', 'district' => 'Soalala'],
            ['code_district' => '404', 'province' => 'Mahajanga', 'region' => 'Melaky', 'district' => 'Ambatomainty'],
            ['code_district' => '406', 'province' => 'Mahajanga', 'region' => 'Melaky', 'district' => 'Antsalova'],
            ['code_district' => '410', 'province' => 'Mahajanga', 'region' => 'Melaky', 'district' => 'Besalampy'],
            ['code_district' => '413', 'province' => 'Mahajanga', 'region' => 'Melaky', 'district' => 'Maintirano'],
            ['code_district' => '418', 'province' => 'Mahajanga', 'region' => 'Melaky', 'district' => 'Morafanobe'],
            ['code_district' => '405', 'province' => 'Mahajanga', 'region' => 'Sofia', 'district' => 'Analalava'],
            ['code_district' => '407', 'province' => 'Mahajanga', 'region' => 'Sofia', 'district' => 'Antsihihy'],
            ['code_district' => '408', 'province' => 'Mahajanga', 'region' => 'Sofia', 'district' => 'Bealanana'],
            ['code_district' => '409', 'province' => 'Mahajanga', 'region' => 'Sofia', 'district' => 'Befandriana Nord'],
            ['code_district' => '414', 'province' => 'Mahajanga', 'region' => 'Sofia', 'district' => 'Mampikony'],
            ['code_district' => '415', 'province' => 'Mahajanga', 'region' => 'Sofia', 'district' => 'Mandritsara'],
            ['code_district' => '419', 'province' => 'Mahajanga', 'region' => 'Sofia', 'district' => 'Port Bergé'],
            ['code_district' => '503', 'province' => 'Toamasina', 'region' => 'Alaotra-Mangoro', 'district' => 'Ambatondrazaka'],
            ['code_district' => '504', 'province' => 'Toamasina', 'region' => 'Alaotra-Mangoro', 'district' => 'Amparafaravola'],
            ['code_district' => '505', 'province' => 'Toamasina', 'region' => 'Alaotra-Mangoro', 'district' => 'Andilamena'],
            ['code_district' => '506', 'province' => 'Toamasina', 'region' => 'Alaotra-Mangoro', 'district' => 'Anosibe'],
            ['code_district' => '514', 'province' => 'Toamasina', 'region' => 'Alaotra-Mangoro', 'district' => 'Moramanga'],
            ['code_district' => '509', 'province' => 'Toamasina', 'region' => 'Analanjirofo', 'district' => 'Fenoarivo Atsinanana'],
            ['code_district' => '511', 'province' => 'Toamasina', 'region' => 'Analanjirofo', 'district' => 'Mananara'],
            ['code_district' => '512', 'province' => 'Toamasina', 'region' => 'Analanjirofo', 'district' => 'Maroansetra'],
            ['code_district' => '515', 'province' => 'Toamasina', 'region' => 'Analanjirofo', 'district' => 'Nosy-Boraha (Ste Marie)'],
            ['code_district' => '516', 'province' => 'Toamasina', 'region' => 'Analanjirofo', 'district' => 'Soanierana-Ivongo'],
            ['code_district' => '518', 'province' => 'Toamasina', 'region' => 'Analanjirofo', 'district' => 'Vavatenina'],
            ['code_district' => '508', 'province' => 'Toamasina', 'region' => 'Atsinanana', 'district' => 'Ampasimanolotra'],
            ['code_district' => '507', 'province' => 'Toamasina', 'region' => 'Atsinanana', 'district' => 'Antanambao Manampotsy'],
            ['code_district' => '510', 'province' => 'Toamasina', 'region' => 'Atsinanana', 'district' => 'Mahanoro'],
            ['code_district' => '513', 'province' => 'Toamasina', 'region' => 'Atsinanana', 'district' => 'Marolambo'],
            ['code_district' => '502', 'province' => 'Toamasina', 'region' => 'Atsinanana', 'district' => 'Toamasina Rural'],
            ['code_district' => '501', 'province' => 'Toamasina', 'region' => 'Atsinanana', 'district' => 'Toamasina Urban'],
            ['code_district' => '517', 'province' => 'Toamasina', 'region' => 'Atsinanana', 'district' => 'Vatomandry'],
            ['code_district' => '604', 'province' => 'Toliara', 'region' => 'Androy', 'district' => 'Ambovombe-Androy'],
            ['code_district' => '607', 'province' => 'Toliara', 'region' => 'Androy', 'district' => 'Bekily'],
            ['code_district' => '609', 'province' => 'Toliara', 'region' => 'Androy', 'district' => 'Beloha'],
            ['code_district' => '621', 'province' => 'Toliara', 'region' => 'Androy', 'district' => 'Tsiombe'],
            ['code_district' => '603', 'province' => 'Toliara', 'region' => 'Anosy', 'district' => 'Amboasary Sud'],
            ['code_district' => '613', 'province' => 'Toliara', 'region' => 'Anosy', 'district' => 'Betroka'],
            ['code_district' => '614', 'province' => 'Toliara', 'region' => 'Anosy', 'district' => 'Taolagnaro'],
            ['code_district' => '605', 'province' => 'Toliara', 'region' => 'Atsimo-Andrefana', 'district' => 'Ampanihy'],
            ['code_district' => '606', 'province' => 'Toliara', 'region' => 'Atsimo-Andrefana', 'district' => 'Ankazoabo Sud'],
            ['code_district' => '610', 'province' => 'Toliara', 'region' => 'Atsimo-Andrefana', 'district' => 'Benenitra'],
            ['code_district' => '611', 'province' => 'Toliara', 'region' => 'Atsimo-Andrefana', 'district' => 'Beroroha'],
            ['code_district' => '612', 'province' => 'Toliara', 'region' => 'Atsimo-Andrefana', 'district' => 'Betioky Sud'],
            ['code_district' => '618', 'province' => 'Toliara', 'region' => 'Atsimo-Andrefana', 'district' => 'Morombe'],
            ['code_district' => '620', 'province' => 'Toliara', 'region' => 'Atsimo-Andrefana', 'district' => 'Sakaraha'],
            ['code_district' => '602', 'province' => 'Toliara', 'region' => 'Atsimo-Andrefana', 'district' => 'Toliara Rural'],
            ['code_district' => '601', 'province' => 'Toliara', 'region' => 'Atsimo-Andrefana', 'district' => 'Toliara Urban'],
            ['code_district' => '608', 'province' => 'Toliara', 'region' => 'Menabe', 'district' => 'Belon-i Tsiribihina'],
            ['code_district' => '615', 'province' => 'Toliara', 'region' => 'Menabe', 'district' => 'Mahabo'],
            ['code_district' => '616', 'province' => 'Toliara', 'region' => 'Menabe', 'district' => 'Manja'],
            ['code_district' => '617', 'province' => 'Toliara', 'region' => 'Menabe', 'district' => 'Miandrivazo'],
            ['code_district' => '619', 'province' => 'Toliara', 'region' => 'Menabe', 'district' => 'Morondava'],
        ]);

        // ============================================
        // Villes de test
        // ============================================
        $this->db->table('villes')->insertBatch([
            ['nom' => 'Antananarivo', 'region' => 'Analamanga'],
            ['nom' => 'Toamasina',    'region' => 'Atsinanana'],
            ['nom' => 'Antsirabe',    'region' => 'Vakinankaratra'],
        ]);

        // ============================================
        // Utilisateurs de test
        // ============================================
        $this->db->table('utilisateurs')->insertBatch([
            [
                'role'              => 'admin',
                'nom'               => 'RAKOTONJANAHARY',
                'prenoms'           => 'Olivier',
                'email'             => 'admin@cb-legaltech.mg',
                'mot_de_passe_hash' => password_hash('password', PASSWORD_DEFAULT),
                'telephone'         => '0346381896',
                'statut_compte'     => 'actif',
            ],
            [
                'role'              => 'proprietaire',
                'nom'               => 'RASOANAIVO',
                'prenoms'           => 'Marie',
                'email'             => 'proprietaire.test@cb-legaltech.mg',
                'mot_de_passe_hash' => password_hash('password', PASSWORD_DEFAULT),
                'telephone'         => '0331234567',
                'statut_compte'     => 'actif',
            ],
            [
                'role'              => 'client',
                'nom'               => 'RAFALIMANANA',
                'prenoms'           => 'Tsiory Fandresena',
                'email'             => 'client.test@cb-legaltech.mg',
                'mot_de_passe_hash' => password_hash('password', PASSWORD_DEFAULT),
                'telephone'         => '0339876543',
                'statut_compte'     => 'actif',
            ],
        ]);
    }
}
