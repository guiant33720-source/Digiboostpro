<?php
// DigiboostPro v1 - Nouvelle commande client
require_once '../config/config.php';

if (!is_logged_in() || !check_role('client')) {
    redirect('/public/login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Récupérer tous les packs
$stmt = $pdo->query("SELECT * FROM packs WHERE actif = TRUE ORDER BY prix ASC");
$packs = $stmt->fetchAll();

// Pack présélectionné
$pack_preselect = $_GET['pack'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide';
    } else {
        $pack_id = intval($_POST['pack_id'] ?? 0);
        $url_site = trim($_POST['url_site'] ?? '');
        $brief = trim($_POST['brief'] ?? '');
        
        if (empty($pack_id) || empty($url_site) || empty($brief)) {
            $error = 'Veuillez remplir tous les champs obligatoires';
        } else {
            // Vérifier que le pack existe
            $stmt = $pdo->prepare("SELECT * FROM packs WHERE id = ? AND actif = TRUE");
            $stmt->execute([$pack_id]);
            $pack = $stmt->fetch();
            
            if (!$pack) {
                $error = 'Pack invalide';
            } else {
                try {
                    // Créer la commande
                    $stmt = $pdo->prepare("
                        INSERT INTO commandes 
                        (client_id, pack_id, url_site, brief, statut, priorite, prix_total, paiement_statut, date_livraison_estimee)
                        VALUES (?, ?, ?, ?, 'en_attente', 'normale', ?, 'en_attente', DATE_ADD(NOW(), INTERVAL ? DAY))
                    ");
                    $stmt->execute([
                        $user_id,
                        $pack_id,
                        $url_site,
                        $brief,
                        $pack['prix'],
                        $pack['delai_livraison']
                    ]);
                    
                    $commande_id = $pdo->lastInsertId();
                    
                    // Gérer l'upload de documents
                    if (isset($_FILES['documents']) && $_FILES['documents']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                        $upload_dir = UPLOAD_PATH . '/documents/';
                        
                        foreach ($_FILES['documents']['tmp_name'] as $key => $tmp_name) {
                            if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                                $file_name = basename($_FILES['documents']['name'][$key]);
                                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                $file_size = $_FILES['documents']['size'][$key];
                                
                                // Vérifier l'extension
                                if (!in_array($file_ext, ALLOWED_FILE_TYPES)) {
                                    continue;
                                }
                                
                                // Vérifier la taille
                                if ($file_size > MAX_FILE_SIZE) {
                                    continue;
                                }
                                
                                // Générer un nom unique
                                $new_file_name = uniqid() . '_' . $file_name;
                                $file_path = $upload_dir . $new_file_name;
                                
                                if (move_uploaded_file($tmp_name, $file_path)) {
                                    // Enregistrer dans la base
                                    $stmt = $pdo->prepare("
                                        INSERT INTO documents (commande_id, nom_fichier, chemin_fichier, type_fichier, taille_fichier, uploaded_by)
                                        VALUES (?, ?, ?, ?, ?, ?)
                                    ");
                                    $stmt->execute([
                                        $commande_id,
                                        $file_name,
                                        $new_file_name,
                                        $file_ext,
                                        $file_size,
                                        $user_id
                                    ]);
                                }
                            }
                        }
                    }
                    
                    // Créer une notification
                    create_notification(
                        $user_id,
                        'commande',
                        'Commande créée',
                        'Votre commande #' . $commande_id . ' a été créée avec succès. Nous vous assignerons un conseiller sous peu.',
                        '/client/commande-detail.php?id=' . $commande_id
                    );
                    
                    // Logger l'activité
                    log_activity($user_id, 'Nouvelle commande', 'Commande #' . $commande_id . ' créée');
                    
                    // Envoyer email de confirmation
                    send_email(
                        $_SESSION['email'],
                        'Confirmation de commande #' . $commande_id,
                        "Bonjour,\n\nVotre commande a été créée avec succès.\n\nService: " . $pack['nom'] . "\nMontant: " . format_price($pack['prix']) . "\n\nNous vous tiendrons informé de l'avancement.\n\nCordialement,\nL'équipe DigiboostPro"
                    );
                    
                    redirect('/client/commande-detail.php?id=' . $commande_id . '&success=1');
                    
                } catch (PDOException $e) {
                    $error = 'Une erreur est survenue lors de la création de la commande';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle commande - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-page">
    <!-- Sidebar -->
    <?php include '../includes/client-sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Nouvelle commande</h1>
            </div>
            <div class="header-right">
                <div class="user-menu">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-info">
                        <strong><?php echo escape($_SESSION['nom_complet']); ?></strong>
                        <span>Client</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Order Form -->
        <div class="content-wrapper">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo escape($error); ?>
                </div>
            <?php endif; ?>

            <div class="order-wizard">
                <!-- Step 1: Choose Pack -->
                <div class="wizard-step active" id="step1">
                    <div class="step-header">
                        <div class="step-number">1</div>
                        <div class="step-info">
                            <h3>Choisissez votre pack</h3>
                            <p>Sélectionnez le service qui correspond à vos besoins</p>
                        </div>
                    </div>

                    <div class="packs-selection">
                        <?php foreach ($packs as $pack): ?>
                            <div class="pack-option <?php echo ($pack_preselect == $pack['id']) ? 'selected' : ''; ?>" 
                                 data-pack-id="<?php echo $pack['id']; ?>" 
                                 data-pack-prix="<?php echo $pack['prix']; ?>">
                                <div class="pack-header">
                                    <input type="radio" 
                                           name="pack_select" 
                                           value="<?php echo $pack['id']; ?>" 
                                           id="pack_<?php echo $pack['id']; ?>"
                                           <?php echo ($pack_preselect == $pack['id']) ? 'checked' : ''; ?>>
                                    <label for="pack_<?php echo $pack['id']; ?>">
                                        <h4><?php echo escape($pack['nom']); ?></h4>
                                        <div class="pack-price"><?php echo format_price($pack['prix']); ?></div>
                                    </label>
                                </div>
                                <p><?php echo escape($pack['description']); ?></p>
                                <ul class="pack-features-mini">
                                    <?php 
                                    $features = array_slice(explode("\n", $pack['features']), 0, 3);
                                    foreach ($features as $feature): 
                                        if (trim($feature)):
                                    ?>
                                        <li><i class="fas fa-check"></i> <?php echo escape($feature); ?></li>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                    <li><i class="fas fa-clock"></i> Livraison <?php echo $pack['delai_livraison']; ?> jours</li>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="wizard-actions">
                        <button type="button" class="btn btn-primary btn-lg" onclick="nextStep(2)">
                            Continuer <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Details -->
                <div class="wizard-step" id="step2">
                    <div class="step-header">
                        <div class="step-number">2</div>
                        <div class="step-info">
                            <h3>Détails du projet</h3>
                            <p>Donnez-nous les informations nécessaires pour bien comprendre votre besoin</p>
                        </div>
                    </div>

                    <form method="POST" action="" enctype="multipart/form-data" id="orderForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="pack_id" id="pack_id_hidden" value="<?php echo $pack_preselect ?? ''; ?>">

                        <div class="form-group">
                            <label for="url_site">
                                <i class="fas fa-globe"></i>
                                URL de votre site web *
                            </label>
                            <input 
                                type="url" 
                                id="url_site" 
                                name="url_site" 
                                class="form-control" 
                                placeholder="https://www.votre-site.com"
                                required
                            >
                            <small class="help-text">
                                <i class="fas fa-info-circle"></i>
                                L'URL complète de votre site à auditer
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="brief">
                                <i class="fas fa-align-left"></i>
                                Brief de votre projet *
                            </label>
                            <textarea 
                                id="brief" 
                                name="brief" 
                                class="form-control" 
                                rows="8" 
                                placeholder="Décrivez votre projet, vos objectifs, votre cible, vos concurrents..."
                                data-maxlength="2000"
                                required
                            ></textarea>
                            <small class="help-text">
                                <i class="fas fa-info-circle"></i>
                                Plus votre brief est détaillé, meilleure sera notre analyse
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="documents">
                                <i class="fas fa-paperclip"></i>
                                Documents complémentaires (optionnel)
                            </label>
                            <div class="file-upload-area drop-zone">
                                <input 
                                    type="file" 
                                    id="documents" 
                                    name="documents[]" 
                                    class="file-input" 
                                    multiple
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"
                                >
                                <label for="documents" class="file-input-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Cliquez pour sélectionner ou glissez-déposez vos fichiers</span>
                                    <small>PDF, DOC, DOCX, JPG, PNG, ZIP (Max 10 MB par fichier)</small>
                                </label>
                                <div id="filesList" class="files-list"></div>
                            </div>
                        </div>

                        <div class="wizard-actions">
                            <button type="button" class="btn btn-secondary btn-lg" onclick="prevStep(1)">
                                <i class="fas fa-arrow-left"></i> Retour
                            </button>
                            <button type="button" class="btn btn-primary btn-lg" onclick="nextStep(3)">
                                Continuer <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 3: Confirmation -->
                <div class="wizard-step" id="step3">
                    <div class="step-header">
                        <div class="step-number">3</div>
                        <div class="step-info">
                            <h3>Récapitulatif et paiement</h3>
                            <p>Vérifiez votre commande avant de valider</p>
                        </div>
                    </div>

                    <div class="order-summary">
                        <div class="summary-card">
                            <h4><i class="fas fa-shopping-cart"></i> Votre commande</h4>
                            
                            <div class="summary-item">
                                <span>Service sélectionné :</span>
                                <strong id="summary_pack_name">-</strong>
                            </div>

                            <div class="summary-item">
                                <span>URL du site :</span>
                                <strong id="summary_url">-</strong>
                            </div>

                            <div class="summary-item">
                                <span>Délai de livraison :</span>
                                <strong id="summary_delai">-</strong>
                            </div>

                            <div class="summary-item">
                                <span>Documents joints :</span>
                                <strong id="summary_files">-</strong>
                            </div>

                            <div class="summary-divider"></div>

                            <div class="summary-total">
                                <span>Montant total :</span>
                                <strong id="summary_price">0,00 €</strong>
                            </div>

                            <div class="payment-info">
                                <h5><i class="fas fa-credit-card"></i> Paiement</h5>
                                <p>Le paiement est simulé en environnement local. En production, cette section intégrera Stripe ou un autre système de paiement sécurisé.</p>
                                
                                <div class="payment-method">
                                    <label class="radio-card">
                                        <input type="radio" name="payment" value="card" checked>
                                        <div class="radio-content">
                                            <i class="fas fa-credit-card"></i>
                                            <span>Carte bancaire</span>
                                        </div>
                                    </label>
                                    <label class="radio-card">
                                        <input type="radio" name="payment" value="paypal">
                                        <div class="radio-content">
                                            <i class="fab fa-paypal"></i>
                                            <span>PayPal</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="accept_terms" required>
                                    <span>J'accepte les <a href="../public/cgu.php" target="_blank">conditions générales de vente</a></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-actions">
                        <button type="button" class="btn btn-secondary btn-lg" onclick="prevStep(2)">
                            <i class="fas fa-arrow-left"></i> Retour
                        </button>
                        <button type="submit" form="orderForm" class="btn btn-primary btn-lg">
                            <i class="fas fa-check"></i> Confirmer et payer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .content-wrapper {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .order-wizard {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }

        .wizard-step {
            display: none;
            padding: 3rem;
        }

        .wizard-step.active {
            display: block;
        }

        .step-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .step-info h3 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            color: var(--gray-900);
        }

        .step-info p {
            color: var(--gray-600);
        }

        .packs-selection {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .pack-option {
            background: var(--white);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 2rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .pack-option:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .pack-option.selected {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .pack-header {
            margin-bottom: 1rem;
        }

        .pack-header input[type="radio"] {
            display: none;
        }

        .pack-header label {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pack-header h4 {
            font-size: 1.25rem;
            color: var(--gray-900);
        }

        .pack-price {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary);
        }

        .pack-option p {
            color: var(--gray-600);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .pack-features-mini {
            list-style: none;
        }

        .pack-features-mini li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
            color: var(--gray-700);
            font-size: 0.9rem;
        }

        .pack-features-mini i {
            color: var(--success);
        }

        .file-upload-area {
            border: 2px dashed var(--gray-300);
            border-radius: var(--radius);
            padding: 2rem;
            text-align: center;
            transition: var(--transition);
        }

        .file-upload-area:hover,
        .file-upload-area.drag-over {
            border-color: var(--primary);
            background: var(--gray-50);
        }

        .file-input {
            display: none;
        }

        .file-input-label {
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            color: var(--gray-700);
        }

        .file-input-label i {
            font-size: 3rem;
            color: var(--primary);
        }

        .file-input-label small {
            color: var(--gray-500);
            font-size: 0.85rem;
        }

        .files-list {
            margin-top: 1.5rem;
            text-align: left;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: var(--gray-50);
            border-radius: var(--radius);
            margin-bottom: 0.5rem;
        }

        .file-item-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .file-item i {
            color: var(--primary);
            font-size: 1.25rem;
        }

        .file-remove {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            padding: 0.5rem;
            transition: var(--transition);
        }

        .file-remove:hover {
            transform: scale(1.2);
        }

        .wizard-actions {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid var(--gray-200);
        }

        .order-summary {
            max-width: 600px;
            margin: 0 auto;
        }

        .summary-card {
            background: var(--gray-50);
            padding: 2rem;
            border-radius: var(--radius-lg);
        }

        .summary-card h4 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            color: var(--gray-900);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .summary-item span {
            color: var(--gray-600);
        }

        .summary-divider {
            height: 2px;
            background: var(--gray-300);
            margin: 1.5rem 0;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            font-size: 1.25rem;
        }

        .summary-total strong {
            color: var(--primary);
            font-size: 1.75rem;
        }

        .payment-info {
            margin: 2rem 0;
            padding: 1.5rem;
            background: var(--white);
            border-radius: var(--radius);
        }

        .payment-info h5 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: var(--gray-900);
        }

        .payment-info p {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .payment-method {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .radio-card {
            cursor: pointer;
        }

        .radio-card input[type="radio"] {
            display: none;
        }

        .radio-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1.5rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .radio-card input[type="radio"]:checked + .radio-content {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .radio-content i {
            font-size: 2rem;
            color: var(--gray-600);
        }

        .radio-card input[type="radio"]:checked + .radio-content i {
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }

            .wizard-step {
                padding: 2rem 1.5rem;
            }

            .packs-selection {
                grid-template-columns: 1fr;
            }

            .wizard-actions {
                flex-direction: column;
            }

            .wizard-actions button {
                width: 100%;
            }

            .payment-method {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        let currentStep = 1;
        let selectedPack = <?php echo $pack_preselect ?? 'null'; ?>;

        // Navigation entre les étapes
        function nextStep(step) {
            // Validation avant de passer à l'étape suivante
            if (step === 2 && !selectedPack) {
                alert('Veuillez sélectionner un pack');
                return;
            }

            if (step === 3) {
                const urlSite = document.getElementById('url_site').value;
                const brief = document.getElementById('brief').value;
                
                if (!urlSite || !brief) {
                    alert('Veuillez remplir tous les champs obligatoires');
                    return;
                }

                // Remplir le récapitulatif
                updateSummary();
            }

            document.getElementById('step' + currentStep).classList.remove('active');
            document.getElementById('step' + step).classList.add('active');
            currentStep = step;
            window.scrollTo(0, 0);
        }

        function prevStep(step) {
            document.getElementById('step' + currentStep).classList.remove('active');
            document.getElementById('step' + step).classList.add('active');
            currentStep = step;
            window.scrollTo(0, 0);
        }

        // Sélection de pack
        document.querySelectorAll('.pack-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.pack-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                selectedPack = radio.value;
                document.getElementById('pack_id_hidden').value = selectedPack;
            });
        });

        // Upload de fichiers
        const fileInput = document.getElementById('documents');
        const filesList = document.getElementById('filesList');
        let uploadedFiles = [];

        fileInput.addEventListener('change', function() {
            displayFiles(this.files);
        });

        function displayFiles(files) {
            filesList.innerHTML = '';
            uploadedFiles = Array.from(files);
            
            uploadedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <div class="file-item-info">
                        <i class="fas fa-file"></i>
                        <span>${file.name} (${(file.size / 1024).toFixed(2)} KB)</span>
                    </div>
                    <button type="button" class="file-remove" onclick="removeFile(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                filesList.appendChild(fileItem);
            });
        }

        function removeFile(index) {
            uploadedFiles.splice(index, 1);
            const dataTransfer = new DataTransfer();
            uploadedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
            displayFiles(fileInput.files);
        }

        // Mise à jour du récapitulatif
        function updateSummary() {
            const packRadio = document.querySelector('input[name="pack_select"]:checked');
            const packOption = packRadio.closest('.pack-option');
            const packName = packOption.querySelector('h4').textContent;
            const packPrice = packOption.querySelector('.pack-price').textContent;
            const packDelai = packOption.querySelector('.pack-features-mini li:last-child').textContent;
            
            document.getElementById('summary_pack_name').textContent = packName;
            document.getElementById('summary_url').textContent = document.getElementById('url_site').value;
            document.getElementById('summary_delai').textContent = packDelai.replace('Livraison ', '');
            document.getElementById('summary_price').textContent = packPrice;
            
            const filesCount = uploadedFiles.length;
            document.getElementById('summary_files').textContent = filesCount > 0 ? filesCount + ' fichier(s)' : 'Aucun';
        }

        // Initialiser avec le pack présélectionné
        if (selectedPack) {
            document.querySelector(`input[value="${selectedPack}"]`).closest('.pack-option').click();
        }
    </script>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>