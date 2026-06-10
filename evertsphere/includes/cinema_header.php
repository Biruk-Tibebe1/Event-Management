<?php
// includes/cinema_header.php
// This file provides the coffee-themed header/head used by cinema pages.
?>
<!doctype html>
<html lang="en" class="h-full">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cinema Management</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <script src="/_sdk/element_sdk.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&amp;family=Source+Sans+3:wght@300;400;500;600&amp;display=swap" rel="stylesheet">
  <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        coffee: {
                            50: '#fdf8f3',
                            100: '#f5e6d3',
                            200: '#e8c9a0',
                            300: '#d4a574',
                            400: '#b8834f',
                            500: '#8B5E3C',
                            600: '#6B4226',
                            700: '#4A2C17',
                            800: '#33200F',
                            900: '#1F1409'
                        }
                    }
                }
            }
        }
    </script>
  <style>
        .font-display { font-family: 'Playfair Display', serif; }
        .font-body { font-family: 'Source Sans 3', sans-serif; }
        .movie-card:hover .movie-overlay { opacity: 1; }
        .movie-card:hover img { transform: scale(1.05); }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-up { animation: fadeUp 0.5s ease forwards; }
        .animate-delay-1 { animation-delay: 0.1s; opacity: 0; }
        .animate-delay-2 { animation-delay: 0.2s; opacity: 0; }
        .animate-delay-3 { animation-delay: 0.3s; opacity: 0; }
        .animate-delay-4 { animation-delay: 0.4s; opacity: 0; }
        .tab-active { border-bottom: 3px solid #8B5E3C; color: #4A2C17; }
    </style>
  <style>body { box-sizing: border-box; }</style>
  <script src="/_sdk/data_sdk.js" type="text/javascript"></script>
 </head>
 <body class="h-full font-body bg-coffee-50 text-coffee-800 overflow-auto">
  <div class="w-full h-full overflow-auto">
   <!-- Header -->
   <header class="bg-coffee-700 text-coffee-50 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
     <div class="flex items-center gap-3">
      <div class="w-10 h-10 bg-coffee-500 rounded-full flex items-center justify-center">
       <i data-lucide="clapperboard" class="w-5 h-5 text-coffee-100"></i>
      </div>
      <h1 id="page-title" class="font-display text-2xl font-bold">Cinema Management</h1>
     </div>
     <nav class="hidden md:flex gap-6 text-coffee-200 text-sm font-medium">
      <a href="#movies" class="hover:text-white transition">Movies</a> <a href="#locations" class="hover:text-white transition">Locations</a> <a href="#schedule" class="hover:text-white transition">Schedule</a>
     </nav><button id="mobile-menu-btn" class="md:hidden text-coffee-200"> <i data-lucide="menu" class="w-6 h-6"></i> </button>
    </div>
   </header>
   <main class="w-full">
