<div class="page-texte">
<h1>Politique de confidentialité</h1>

<p>
    Cette page explique quelles données personnelles le site collecte, pourquoi, combien de temps
    elles sont gardées et comment exercer vos droits, conformément au Règlement général sur la protection
    des données (RGPD).
</p>

<h2>Responsable du traitement</h2>
<p>
    <?= info_legale($legal, 'editeur') ?>, <?= info_legale($legal, 'adresse') ?>.<br>
    Contact pour toute question sur vos données : <?= info_legale($legal, 'email') ?>
</p>

<h2>Données collectées</h2>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th scope="col">Personnes</th>
                <th scope="col">Données</th>
                <th scope="col">Pourquoi</th>
                <th scope="col">Durée de conservation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th scope="row">Étudiants</th>
                <td>Nom, prénom, réponses choisies, score, date et heure du passage</td>
                <td>Permettre au formateur d'évaluer les acquis et de suivre les résultats</td>
                <td>Jusqu'à leur suppression par le formateur, et au plus <?= (int) $conservationMois ?> mois après le passage (suppression automatique)</td>
            </tr>
            <tr>
                <th scope="row">Formateurs</th>
                <td>Nom, adresse e-mail, mot de passe (enregistré sous une forme chiffrée irréversible), date de création du compte</td>
                <td>Créer le compte et donner accès à ses QCM</td>
                <td>Tant que le compte existe ; suppression sur demande</td>
            </tr>
            <tr>
                <th scope="row">Formateurs</th>
                <td>Journal technique : type d'événement (connexion, QCM créé, passage terminé), date, identifiants techniques. Aucun nom d'étudiant.</td>
                <td>Sécurité du service et suivi de l'activité</td>
                <td><?= (int) $conservationMois ?> mois</td>
            </tr>
        </tbody>
    </table>
</div>
<p>
    Les statistiques par question (taux de réussite, réponse la plus choisie) sont des compteurs anonymes :
    elles ne permettent pas de savoir qui a répondu quoi.
</p>

<h2>Base légale</h2>
<ul>
    <li><strong>Compte formateur</strong> : exécution du service demandé par le formateur (article 6.1.b du RGPD).</li>
    <li><strong>Résultats des étudiants</strong> : intérêt légitime de l'organisme de formation à évaluer les connaissances (article 6.1.f).</li>
    <li><strong>Journal et blocage après plusieurs mots de passe faux</strong> : intérêt légitime à sécuriser le service (article 6.1.f).</li>
</ul>

<h2>Qui voit les données</h2>
<p>
    Les résultats d'un QCM ne sont visibles que par le formateur qui l'a créé.
    Les données ne sont ni vendues, ni transmises à des tiers, ni utilisées pour de la publicité.
    Elles sont stockées chez l'hébergeur du site (<?= info_legale($legal, 'hebergeur') ?>), dans l'Union européenne.
</p>

<h2>Cookies</h2>
<p>Le site n'utilise que des cookies nécessaires à son fonctionnement. Il n'y a ni cookie publicitaire, ni mesure d'audience : aucun consentement n'est donc demandé.</p>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th scope="col">Cookie</th>
                <th scope="col">Rôle</th>
                <th scope="col">Durée</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th scope="row">qcm_session</th>
                <td>Garder la connexion du formateur, le passage en cours de l'étudiant et le jeton de sécurité des formulaires</td>
                <td>Jusqu'à la fermeture du navigateur ; déconnexion après <?= (int) $inactiviteHeures ?> heures d'inactivité</td>
            </tr>
            <tr>
                <th scope="row">qcm_termine_…</th>
                <td>Retenir qu'un QCM a déjà été terminé dans ce navigateur, pour qu'il ne soit passé qu'une fois. Ne contient qu'un numéro de passage.</td>
                <td>1 an</td>
            </tr>
        </tbody>
    </table>
</div>

<h2>Sécurité</h2>
<p>
    Les mots de passe sont chiffrés de façon irréversible, les échanges passent par une connexion sécurisée (HTTPS)
    et un compte est bloqué temporairement après plusieurs mots de passe faux. Les bonnes réponses d'un QCM ne sont
    envoyées au navigateur qu'une fois le QCM terminé.
</p>

<h2>Vos droits</h2>
<p>
    Vous pouvez demander l'accès à vos données, leur correction, leur suppression ou la limitation de leur utilisation,
    et vous opposer à leur traitement.
</p>
<ul>
    <li><strong>Étudiants</strong> : adressez-vous au formateur qui vous a transmis le QCM, qui peut corriger ou supprimer votre résultat, ou écrivez à <?= info_legale($legal, 'email') ?>.</li>
    <li><strong>Formateurs</strong> : écrivez à <?= info_legale($legal, 'email') ?>, notamment pour supprimer votre compte.</li>
</ul>
<p>
    Une réponse vous est apportée sous un mois. Si vous estimez que vos droits ne sont pas respectés,
    vous pouvez adresser une réclamation à la CNIL (<a href="https://www.cnil.fr" rel="noopener">www.cnil.fr</a>).
</p>
</div>
