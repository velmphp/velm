export type BadgeLink = {
  href: string;
  src: string;
  alt: string;
};

/** Shields.io `for-the-badge` badges — keep in sync with README.md */
export const projectBadges: BadgeLink[] = [
  {
    href: 'https://github.com/velmphp/velm/actions/workflows/ci.yml',
    src: 'https://img.shields.io/github/actions/workflow/status/velmphp/velm/ci.yml?branch=main&style=for-the-badge&logo=github&label=CI',
    alt: 'CI',
  },
  {
    href: 'https://codecov.io/gh/velmphp/velm',
    src: 'https://img.shields.io/codecov/c/github/velmphp/velm?branch=main&style=for-the-badge&logo=codecov&label=coverage',
    alt: 'Test coverage',
  },
  {
    href: 'https://packagist.org/packages/velmphp/framework',
    src: 'https://img.shields.io/packagist/v/velmphp/framework?style=for-the-badge&logo=packagist&logoColor=white&label=Packagist',
    alt: 'Packagist version',
  },
  {
    href: 'https://packagist.org/packages/velmphp/framework/stats',
    src: 'https://img.shields.io/packagist/dt/velmphp/framework?style=for-the-badge&logo=packagist&logoColor=white&label=downloads',
    alt: 'Packagist downloads',
  },
  {
    href: 'https://www.php.net/releases/8.3.php',
    src: 'https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white',
    alt: 'PHP 8.3+',
  },
  {
    href: 'https://github.com/velmphp/velm/blob/main/LICENSE',
    src: 'https://img.shields.io/badge/License-MIT-blue?style=for-the-badge',
    alt: 'License MIT',
  },
  {
    href: 'https://laravel.com',
    src: 'https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white',
    alt: 'Laravel 13',
  },
  {
    href: 'https://livewire.laravel.com',
    src: 'https://img.shields.io/badge/Livewire-4-FB70A9?style=for-the-badge&logo=livewire&logoColor=white',
    alt: 'Livewire 4',
  },
  {
    href: 'https://pestphp.com',
    src: 'https://img.shields.io/badge/Pest-4-6ADE80?style=for-the-badge&logo=pest&logoColor=white',
    alt: 'Pest 4',
  },
];
