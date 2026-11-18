<?php
/**
 * En-tête et navigation communs.
 * Variable : $current (optionnelle) = 'home' | 'spots', pour marquer le lien actif
 */
$current = $current ?? '';
?>
<header class="site-header">
    <nav class="container">
        <ul>
            <li>
                <a href="/" class="logo">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.5 14.2A8.6 8.6 0 0 1 9.8 3.5a8.6 8.6 0 1 0 10.7 10.7z" />
                    </svg>
                    site4night
                </a>
            </li>
        </ul>
        <ul>
            <li><a href="/" <?= $current === 'home' ? 'aria-current="page"' : '' ?>>Accueil</a></li>
            <li><a href="/spots/" <?= $current === 'spots' ? 'aria-current="page"' : '' ?>>Lieux</a></li>
        </ul>
    </nav>
</header>
