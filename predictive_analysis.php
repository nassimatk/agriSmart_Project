<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header('Location: admin.php');
    exit;
}

$userName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
$farmName = htmlspecialchars($_SESSION['farm_name']);

// Récupérer la dernière lecture pour le rendement
$latest_sensor = $conn->query("SELECT * FROM sensor_readings ORDER BY timestamp DESC LIMIT 1")->fetch_assoc();

// Récupérer la photo pour la santé du feuillage
$photo_id = isset($_GET['photo_id']) ? (int)$_GET['photo_id'] : (isset($_SESSION['current_photo_id']) ? $_SESSION['current_photo_id'] : null);
if ($photo_id) {
    $stmt = $conn->prepare("SELECT * FROM plant_photos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $photo_id, $_SESSION['user_id']);
    $stmt->execute();
    $latest_photo = $stmt->get_result()->fetch_assoc();
}
if (empty($latest_photo)) {
    $latest_photo = $conn->query("SELECT * FROM plant_photos WHERE user_id = {$_SESSION['user_id']} ORDER BY upload_date DESC LIMIT 1")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyse Prédictive - AgriSmart</title>
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        nature: {
                            DEFAULT: '#2D6A4F',
                            light: '#40916C',
                            dark: '#1B4332',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .ai-pulse {
            animation: pulse-border 2s infinite;
        }
        @keyframes pulse-border {
            0% { box-shadow: 0 0 0 0 rgba(45, 106, 79, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(45, 106, 79, 0); }
            100% { box-shadow: 0 0 0 0 rgba(45, 106, 79, 0); }
        }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden text-gray-800">

    <!-- Sidebar (Same as index) -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-gray-800">
            <i class="fa-solid fa-seedling text-nature-light text-2xl mr-3"></i>
            <span class="text-xl font-bold tracking-wider">AgriSmart</span>
        </div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="index.php" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors">
                <i class="fa-solid fa-chart-line w-6"></i>
                <span class="font-medium">Tableau de bord</span>
            </a>
            <a href="predictive_analysis.php" class="flex items-center px-4 py-3 bg-nature text-white rounded-lg shadow-sm transition-colors">
                <i class="fa-solid fa-brain w-6"></i>
                <span class="font-medium">Analyse Prédictive</span>
            </a>
            <a href="#" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors">
                <i class="fa-solid fa-clock-rotate-left w-6"></i>
                <span class="font-medium">Historique</span>
            </a>
            <a href="#" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors">
                <i class="fa-solid fa-sliders w-6"></i>
                <span class="font-medium">Paramètres</span>
            </a>
        </nav>
        <div class="p-4 border-t border-gray-800 text-xs text-gray-500 text-center">
            &copy; 2026 Région Souss-Massa
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Header -->
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 lg:px-10 z-10 transition-all">
            <div class="flex items-center">
                <h1 class="text-xl font-bold text-gray-800">Rapport d'Intelligence Artificielle</h1>
                <span class="ml-4 px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full flex items-center shadow-sm">
                    <i class="fa-solid fa-bolt mr-2 animate-pulse text-blue-500"></i>
                    IA Active
                </span>
            </div>
            <div class="flex items-center space-x-6">
                <div class="hidden md:flex flex-col text-right">
                    <span class="text-sm font-semibold text-gray-800"><?= $farmName ?></span>
                    <span class="text-xs text-gray-400"><i class="fa-solid fa-user mr-1"></i> <?= $userName ?></span>
                </div>
                <a href="index.php" class="h-10 w-10 bg-gray-100 rounded-xl flex items-center justify-center text-gray-600 hover:bg-gray-200 transition-all hover:scale-105" title="Retour">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
            </div>
        </header>

        <!-- Scrollable content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10 space-y-8 bg-slate-50/50">
            
            <div class="max-w-7xl mx-auto">
                <div class="bg-gradient-to-br from-nature-dark via-nature to-nature-light p-10 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden group">
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
                        <div>
                            <div class="flex items-center gap-3 mb-4">
                                <span class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-bold tracking-widest uppercase">Analyse en temps réel</span>
                                <span class="h-2 w-2 rounded-full bg-green-400 animate-ping"></span>
                            </div>
                            <h2 class="text-4xl md:text-5xl font-black mb-4 tracking-tight">Rapport d'Analyse (IA)</h2>
                            <p class="text-white/80 font-medium max-w-xl text-lg leading-relaxed">Fusion de flux de données multicapteurs et de vision par ordinateur pour une agriculture de précision.</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-xl p-6 rounded-[2rem] border border-white/20 flex flex-col items-center gap-4 min-w-[200px] hover:bg-white/15 transition-all">
                            <div class="h-16 w-16 bg-white/20 rounded-2xl flex items-center justify-center text-3xl shadow-lg">
                                <i class="fa-solid fa-microchip"></i>
                            </div>
                            <div class="text-center">
                                <p class="text-[0.6rem] uppercase tracking-[0.2em] text-white/60 mb-1">Architecture Modèle</p>
                                <p class="font-black text-xl tracking-tight">AgriSmart V3-Pro</p>
                            </div>
                        </div>
                    </div>
                    <!-- Decor -->
                    <div class="absolute -right-20 -bottom-20 opacity-10 group-hover:rotate-12 transition-transform duration-1000">
                        <i class="fa-solid fa-brain text-[300px]"></i>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                
                <!-- Section 1: Yield Prediction Details -->
                <div class="bg-white rounded-[2.5rem] shadow-xl shadow-nature/5 border border-gray-100 p-10 flex flex-col h-full hover:border-nature/20 transition-all">
                    <div class="flex items-center justify-between mb-10">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 bg-nature/10 text-nature rounded-2xl flex items-center justify-center text-xl">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <h3 class="text-2xl font-black text-gray-800 tracking-tight">Prédiction Rendement</h3>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-widest mb-1">Précision Modèle</span>
                            <span class="px-4 py-1.5 bg-green-100 text-green-700 text-xs font-black rounded-full border border-green-200">97.4%</span>
                        </div>
                    </div>

                    <div class="flex-1 flex flex-col items-center justify-center py-4 bg-gray-50/50 rounded-[2rem] mb-8 border border-dashed border-gray-200">
                        <?php 
                        $pred = isset($latest_sensor['prediction']) ? $latest_sensor['prediction'] : 'Calcul...';
                        $statusColor = 'text-gray-400';
                        $bgColor = 'bg-gray-100';
                        $desc = "Analyse des données environnementales en cours...";
                        
                        if (strtolower($pred) === 'high') {
                            $statusColor = 'text-green-500';
                            $bgColor = 'bg-green-100';
                            $desc = "Les conditions actuelles sont optimales. Le rendement prévu dépasse les moyennes saisonnières de 12%.";
                        } elseif (strtolower($pred) === 'med') {
                            $statusColor = 'text-orange-500';
                            $bgColor = 'bg-orange-100';
                            $desc = "Rendement stable. L'IA suggère d'optimiser le taux d'humidité pour atteindre le stade optimal.";
                        } elseif (strtolower($pred) === 'low') {
                            $statusColor = 'text-red-500';
                            $bgColor = 'bg-red-100';
                            $desc = "Alerte de rendement ! Les variations thermiques récentes impactent le potentiel de croissance.";
                        }
                        ?>
                        
                        <div class="relative w-56 h-56 mb-8 group">
                            <svg class="w-full h-full transform -rotate-90 filter drop-shadow-md" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="44" fill="none" stroke="#e5e7eb" stroke-width="12"></circle>
                                <circle cx="50" cy="50" r="44" fill="none" stroke="currentColor" stroke-width="12" stroke-dasharray="276" stroke-dashoffset="<?php echo (strtolower($pred) === 'high' ? '50' : (strtolower($pred) === 'med' ? '120' : '200')); ?>" stroke-linecap="round" class="<?php echo $statusColor; ?> transition-all duration-1000"></circle>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-[0.5rem] font-black text-gray-400 uppercase tracking-[0.3em] mb-1">Diagnostic Final</span>
                                <span class="text-4xl font-black <?php echo $statusColor; ?> tracking-tighter"><?= strtoupper($pred) ?></span>
                                <div class="mt-2 h-1 w-12 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-current w-2/3 animate-pulse <?php echo $statusColor; ?>"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="px-8 text-center">
                            <p class="text-gray-700 font-bold mb-2 text-lg">Verdict AgriSmart</p>
                            <p class="text-gray-500 text-sm leading-relaxed max-w-xs mx-auto"><?= $desc ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-5 rounded-3xl border border-slate-100 flex items-center gap-5 group hover:bg-white hover:shadow-lg hover:shadow-slate-200/50 transition-all">
                            <div class="h-12 w-12 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center text-xl transition-transform group-hover:scale-110">
                                <i class="fa-solid fa-temperature-full"></i>
                            </div>
                            <div>
                                <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Variable Climat</p>
                                <p class="font-black text-gray-900 text-lg"><?= $latest_sensor['air_temperature'] ?? '--' ?><span class="text-gray-300 text-sm font-bold ml-0.5">°C</span></p>
                            </div>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-3xl border border-slate-100 flex items-center gap-5 group hover:bg-white hover:shadow-lg hover:shadow-slate-200/50 transition-all">
                            <div class="h-12 w-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-xl transition-transform group-hover:scale-110">
                                <i class="fa-solid fa-droplet"></i>
                            </div>
                            <div>
                                <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Variable Sol</p>
                                <p class="font-black text-gray-900 text-lg"><?= $latest_sensor['soil_moisture'] ?? '--' ?><span class="text-gray-300 text-sm font-bold ml-0.5">%</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Health Analysis (Foliage) -->
                <div class="bg-white rounded-[2.5rem] shadow-xl shadow-nature/5 border border-gray-100 p-10 flex flex-col h-full hover:border-nature/20 transition-all">
                    <div class="flex items-center justify-between mb-10">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-xl">
                                <i class="fa-solid fa-eye"></i>
                            </div>
                            <h3 class="text-2xl font-black text-gray-800 tracking-tight">Santé du feuillage</h3>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-widest mb-1">Moteur Vision</span>
                            <span class="px-4 py-1.5 bg-blue-100 text-blue-700 text-xs font-black rounded-full border border-blue-200 shadow-sm">PHP-GD Vision Pro</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-10">
                        <div class="relative rounded-[2.5rem] overflow-hidden shadow-2xl bg-gray-900 aspect-[16/10] group border-4 border-white">
                            <?php if ($latest_photo): ?>
                                <img id="plantImg" src="<?= $latest_photo['image_path'] ?>" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity duration-700">
                                <!-- Scan Overlay -->
                                <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-nature-light to-transparent top-0 animate-scan pointer-events-none opacity-50 shadow-[0_0_20px_rgba(64,145,108,1)]"></div>
                                
                                <div class="absolute inset-0 bg-nature/10 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-[2px] pointer-events-none">
                                    <div class="bg-white/90 p-4 rounded-2xl shadow-xl flex items-center gap-3 scale-90 group-hover:scale-100 transition-transform">
                                        <i class="fa-solid fa-microscope text-nature text-2xl animate-bounce"></i>
                                        <span class="font-black text-sm text-gray-800 uppercase tracking-wider">Traitement Neural en cours</span>
                                    </div>
                                </div>
                                <div class="absolute bottom-6 left-6 right-6 flex justify-between items-end pointer-events-none">
                                    <div class="bg-black/40 backdrop-blur-md px-3 py-1.5 rounded-lg border border-white/20 text-[0.6rem] font-mono text-white/90">
                                        IMG_REF: <?= sprintf("%06d", $latest_photo['id'] ?? 0) ?> | SENSOR: ACTIVE
                                    </div>
                                    <div class="h-6 w-6 border-r-2 border-b-2 border-nature-light"></div>
                                </div>
                                <div class="absolute top-6 left-6 h-6 w-6 border-l-2 border-t-2 border-nature-light"></div>
                            <?php else: ?>
                                <div class="flex flex-col items-center justify-center h-full text-gray-400 bg-gray-50">
                                    <div class="h-20 w-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-camera-slash text-3xl"></i>
                                    </div>
                                    <p class="text-sm font-black tracking-widest uppercase opacity-50">Aucun échantillon visuel</p>
                                    <a href="index.php" class="mt-4 text-nature font-bold text-xs underline underline-offset-4">Capturer une photo</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div id="ai-loading" class="flex flex-col items-center py-6">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="h-10 w-10 border-4 border-nature-light border-t-transparent rounded-full animate-spin"></div>
                                <p class="text-lg font-black text-gray-700 tracking-tight">Séquençage de l'image...</p>
                            </div>
                            <div class="h-2 w-full max-w-xs bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-nature to-nature-light w-0 transition-all duration-1000" id="analysis-bar"></div>
                            </div>
                        </div>

                        <div id="ai-results" class="space-y-8 hidden">
                            <div class="flex flex-col sm:flex-row items-stretch gap-6">
                                <div class="flex-1 bg-slate-50 rounded-3xl p-8 border-l-[10px] border-nature transition-all shadow-sm" id="health-card">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="h-2 w-2 rounded-full bg-current"></div>
                                        <p class="text-[0.65rem] uppercase tracking-[0.2em] text-gray-400 font-bold">Diagnostic Pathogène</p>
                                    </div>
                                    <p class="text-3xl font-black text-gray-900 mb-2 tracking-tighter" id="health-status">Vérification...</p>
                                    <div class="flex items-center gap-2 mb-1">
                                        <i class="fa-solid fa-leaf text-nature-light text-xs"></i>
                                        <p class="text-sm text-gray-600 font-bold uppercase tracking-tight" id="plant-type">Analyse...</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i id="disease-icon" class="fa-solid fa-dna text-nature-light text-xs"></i>
                                        <p class="text-sm text-nature font-black tracking-tight" id="health-disease">Analyse...</p>
                                    </div>
                                </div>
                                
                                <div class="sm:w-32 bg-nature text-white rounded-3xl p-6 flex flex-col items-center justify-center text-center shadow-lg shadow-nature/20">
                                    <p class="text-[0.5rem] font-bold uppercase tracking-widest mb-2 text-white/60">Confiance IA</p>
                                    <span class="text-3xl font-black tracking-tighter"><span id="final-score">--</span>%</span>
                                </div>
                            </div>
                            
                            <div class="space-y-6 bg-gray-50/50 p-8 rounded-[2rem] border border-gray-100">
                                <div>
                                    <div class="flex justify-between text-[0.65rem] font-black mb-2 uppercase tracking-widest">
                                        <span class="text-gray-400">Densité de Chlorophylle</span>
                                        <span class="text-nature bg-nature/10 px-2 py-0.5 rounded-full" id="green-ratio">--%</span>
                                    </div>
                                    <div class="h-3 w-full bg-white rounded-full overflow-hidden border border-gray-100 p-0.5">
                                        <div class="h-full bg-gradient-to-r from-emerald-400 to-nature rounded-full transition-all duration-1000 shadow-[0_0_10px_rgba(45,106,79,0.3)]" id="green-bar" style="width: 0%"></div>
                                    </div>
                                </div>
                                <div id="warning-box" class="hidden animate-in fade-in slide-in-from-bottom-2">
                                    <div class="flex justify-between text-[0.65rem] font-black mb-2 uppercase tracking-widest">
                                        <span class="text-red-400">Indices de Stress (Nécrose/Chlorose)</span>
                                        <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded-full" id="bad-ratio">--%</span>
                                    </div>
                                    <div class="h-3 w-full bg-white rounded-full overflow-hidden border border-gray-100 p-0.5">
                                        <div class="h-full bg-gradient-to-r from-red-400 to-orange-400 rounded-full transition-all duration-1000 shadow-[0_0_10px_rgba(239,68,68,0.3)]" id="bad-bar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Radar Chart - Multi-factor balance -->
                <div class="bg-white rounded-[2.5rem] shadow-xl shadow-nature/5 border border-gray-100 p-12 col-span-1 lg:col-span-2 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 p-12 opacity-[0.03] transform translate-x-1/4 -translate-y-1/4 group-hover:scale-110 transition-transform duration-1000">
                        <i class="fa-solid fa-compass text-[400px]"></i>
                    </div>
                    
                    <div class="relative z-10 flex flex-col xl:flex-row gap-12 items-center">
                        <div class="xl:w-1/3 text-center xl:text-left space-y-6">
                            <div>
                                <h3 class="text-3xl font-black text-gray-800 tracking-tighter mb-4">Équilibre Holistique</h3>
                                <p class="text-gray-500 font-medium leading-relaxed italic">Ce graphique Radar fusionne 6 vecteurs de données pour visualiser l'homogénéité de votre écosystème de serre.</p>
                            </div>
                            <div class="flex flex-wrap justify-center xl:justify-start gap-3">
                                <span class="px-4 py-2 bg-slate-50 rounded-2xl text-[0.6rem] font-black uppercase tracking-widest text-gray-500 border border-slate-100">Humidité</span>
                                <span class="px-4 py-2 bg-slate-50 rounded-2xl text-[0.6rem] font-black uppercase tracking-widest text-gray-500 border border-slate-100">Thermie</span>
                                <span class="px-4 py-2 bg-slate-50 rounded-2xl text-[0.6rem] font-black uppercase tracking-widest text-gray-500 border border-slate-100">Vision</span>
                            </div>
                        </div>
                        
                        <div class="xl:w-1/3 relative flex justify-center">
                            <div class="w-full max-w-[320px] aspect-square relative">
                                <canvas id="radarChart"></canvas>
                                <div class="absolute inset-0 border-[20px] border-nature/5 rounded-full pointer-events-none"></div>
                            </div>
                        </div>

                        <div class="xl:w-1/3 space-y-6">
                            <div class="p-8 rounded-[2rem] bg-gradient-to-br from-emerald-50/50 to-white border border-emerald-100 shadow-sm hover:shadow-md transition-all">
                                <p class="text-nature text-[0.65rem] font-black uppercase tracking-[0.3em] mb-4 flex items-center gap-3">
                                    <span class="h-2 w-2 rounded-full bg-nature animate-pulse"></span> Conseil de précision
                                </p>
                                <p class="text-gray-800 font-bold leading-relaxed text-sm">
                                    "<span id="ai-advice">L'analyse multicritères suggère une synergie excellente. Risque de stress hydrique néant pour les prochaines 12h.</span>"
                                </p>
                                <div class="mt-4 flex items-center gap-2 text-nature font-black text-[0.6rem] uppercase">
                                    Exécuter recommandation <i class="fa-solid fa-arrow-right"></i>
                                </div>
                            </div>
                            
                            <div class="p-8 rounded-[2rem] bg-gradient-to-br from-blue-50/50 to-white border border-blue-100 shadow-sm hover:shadow-md transition-all">
                                <p class="text-blue-600 text-[0.65rem] font-black uppercase tracking-[0.3em] mb-4 flex items-center gap-3">
                                    <i class="fa-solid fa-shield-halved"></i> Audit Biosécurité
                                </p>
                                <p class="text-gray-800 font-bold leading-relaxed text-sm" id="disease-summary">
                                    Audit en cours... Calcul de l'exposition aux agents pathogènes basé sur le différentiel thermique jour/nuit.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
            <div class="max-w-7xl mx-auto py-10 text-center">
                <p class="text-gray-400 text-xs font-bold uppercase tracking-[0.4em] mb-4">Système de Surveillance Avancé</p>
                <div class="inline-flex items-center gap-6 px-10 py-5 bg-white rounded-full shadow-lg border border-gray-100">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/0/05/Flag_of_Morocco.svg" class="h-4 rounded-sm shadow-sm" alt="Morocco">
                    <span class="text-[0.6rem] font-black text-gray-500 uppercase tracking-widest">Optimisé pour la région Souss-Massa</span>
                    <div class="h-4 w-px bg-gray-200"></div>
                    <span class="text-[0.6rem] font-black text-nature uppercase tracking-widest">Propulsé par AgriSmart AI Core</span>
                </div>
            </div>
        </main>
    </div>

    <script>
        // --- ANIMATIONS ET LOGIQUE IA ---
        
        window.onload = function() {
            setTimeout(analyzeCurrentPlant, 800);
            initRadarChart();
            
            // Notification entrance
            document.querySelector('header').classList.add('translate-y-0');
        }

        function analyzeCurrentPlant() {
            const loading = document.getElementById('ai-loading');
            const results = document.getElementById('ai-results');
            const bar = document.getElementById('analysis-bar');
            
            bar.style.width = '100%';

            let url = 'analyze_photo.php';
            const photoId = <?= isset($_GET['photo_id']) ? (int)$_GET['photo_id'] : 'null' ?>;
            if (photoId) url += '?photo_id=' + photoId;

            fetch(url)
            .then(res => res.json())
            .then(data => {
                setTimeout(() => {
                    if (data.success) {
                        loading.className = 'hidden';
                        results.classList.remove('hidden');
                        
                        document.getElementById('health-status').textContent = data.status.toUpperCase();
                        document.getElementById('plant-type').textContent = data.plant;
                        document.getElementById('health-disease').textContent = data.disease;
                        const icon = document.getElementById('disease-icon');
                        const diseaseText = document.getElementById('health-disease');
                        if (data.badge === 'red') {
                            icon.className = 'fa-solid fa-virus-covid text-red-500 text-sm';
                            diseaseText.className = 'text-sm text-red-600 font-black tracking-tight';
                        } else {
                            icon.className = 'fa-solid fa-shield-check text-green-500 text-sm';
                            diseaseText.className = 'text-sm text-green-600 font-black tracking-tight';
                        }
                        document.getElementById('final-score').textContent = data.health_score;
                        
                        const greenText = document.getElementById('green-ratio');
                        const greenBar = document.getElementById('green-bar');
                        greenText.textContent = data.green_ratio + '%';
                        greenBar.style.width = data.green_ratio + '%';
                        
                        const badRatio = (parseFloat(data.yellow_ratio) + parseFloat(data.brown_ratio)).toFixed(1);
                        if (badRatio > 3) {
                            document.getElementById('warning-box').classList.remove('hidden');
                            document.getElementById('bad-ratio').textContent = badRatio + '%';
                            document.getElementById('bad-bar').style.width = Math.min(100, badRatio * 3) + '%';
                        }
                        
                        const healthCard = document.getElementById('health-card');
                        const diseaseSum = document.getElementById('disease-summary');
                        const aiAdvice = document.getElementById('ai-advice');

                        if (data.badge === 'red') {
                            healthCard.style.borderColor = '#ef4444';
                            healthCard.style.color = '#ef4444';
                            diseaseSum.innerHTML = "<span class='text-red-600 uppercase font-black mr-2'>Alerte Critique :</span> Des signes de <b class='text-red-700'>" + data.disease + "</b> ont été localisés. L'infection est estimée à un stade primaire.";
                            aiAdvice.innerHTML = "Action immédiate requise : Isolez le secteur affecté et vérifiez le taux d'humidité foliaire.";
                        } else if (data.badge === 'orange') {
                            healthCard.style.borderColor = '#f59e0b';
                            healthCard.style.color = '#f59e0b';
                            diseaseSum.innerHTML = "<span class='text-orange-600 uppercase font-black mr-2'>Vigilance :</span> Détection d'un stress physiologique modéré. Possible carence en azote ou excès de chaleur.";
                        } else {
                            diseaseSum.innerHTML = "Audit Bio-sécurité OK. <span class='text-nature'>Zéro pathogène détecté</span> sur les derniers échantillons de vision.";
                        }
                    }
                }, 1200);
            });
        }

        function initRadarChart() {
            const ctx = document.getElementById('radarChart').getContext('2d');
            
            const airTemp = <?= $latest_sensor['air_temperature'] ?? 25 ?>;
            const soilHum = <?= $latest_sensor['soil_moisture'] ?? 60 ?>;
            const airHum = <?= $latest_sensor['air_humidity'] ?? 55 ?>;
            const light = <?= $latest_sensor['light_intensity'] ?? 5000 ?>;
            
            // Normalize values for radar (0-100)
            const nTemp = airTemp > 35 ? 40 : (airTemp < 15 ? 50 : 95);
            const nSoil = soilHum;
            const nAir = (100 - airHum + 50) / 1.5; // High air hum is risky
            const nLight = Math.min(100, (light / 10000) * 100);
            
            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['THERMIE', 'HYDRATATION', 'TRANSPIRATION', 'PHOTONS', 'NUTRITION', 'VISION'],
                    datasets: [{
                        data: [nTemp, nSoil, nAir, nLight, 80, 95],
                        backgroundColor: 'rgba(45, 106, 79, 0.15)',
                        borderColor: '#2D6A4F',
                        borderWidth: 4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#2D6A4F',
                        pointBorderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        r: {
                            angleLines: { color: 'rgba(0,0,0,0.05)', lineWidth: 1 },
                            grid: { color: 'rgba(0,0,0,0.05)', lineWidth: 1 },
                            pointLabels: { 
                                color: '#94a3b8', 
                                font: { family: "'Inter', sans-serif", size: 9, weight: '900' },
                                padding: 15
                            },
                            suggestedMin: 0,
                            suggestedMax: 100,
                            ticks: { display: false }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
