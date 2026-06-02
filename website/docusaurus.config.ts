import { themes as prismThemes } from 'prism-react-renderer';
import type { Config } from '@docusaurus/types';
import type * as Preset from '@docusaurus/preset-classic';

/** GitHub Pages project site: https://velmphp.github.io/velm/ */
const siteUrl = process.env.DOCUSAURUS_URL ?? 'http://localhost:3000';
const baseUrl = process.env.DOCUSAURUS_BASE_URL ?? '/velm/';

const config: Config = {
  title: 'Velm',
  tagline: 'A truly decoupled modular architecture for Laravel',
  url: siteUrl,
  baseUrl,
  organizationName: 'velmphp',
  projectName: 'velm',
  onBrokenLinks: 'throw',
  markdown: {
    hooks: {
      onBrokenMarkdownLinks: 'warn',
    },
  },
  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },
  presets: [
    [
      'classic',
      {
        docs: {
          routeBasePath: 'docs',
          sidebarPath: './sidebars.ts',
          editUrl: 'https://github.com/velmphp/velm/tree/main/website/',
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
      } satisfies Preset.Options,
    ],
  ],
  themeConfig: {
    image: 'img/logo-concept.svg',
    colorMode: {
      defaultMode: 'light',
      respectPrefersColorScheme: true,
      disableSwitch: false,
    },
    navbar: {
      title: 'Velm',
      logo: {
        alt: 'Velm',
        src: 'img/banner-dark.svg',
        srcDark: 'img/banner.svg',
        height: 48,
      },
      items: [
        {
          to: '/',
          label: 'Home',
          position: 'left',
          activeBaseRegex: '^/$',
        },
        {
          type: 'docSidebar',
          sidebarId: 'docs',
          position: 'left',
          label: 'Docs',
        },
        {
          href: 'https://github.com/velmphp/velm',
          label: 'GitHub',
          position: 'right',
        },
        {
          href: 'https://github.com/velmphp/docs',
          label: 'Docs repo',
          position: 'right',
        },
      ],
    },
    footer: {
      style: 'dark',
      links: [
        {
          title: 'Docs',
          items: [
            { label: 'Introduction', to: '/docs/intro' },
            { label: 'Installation', to: '/docs/guides/installation' },
            { label: 'Extending models', to: '/docs/models/extending-a-model' },
          ],
        },
        {
          title: 'Community',
          items: [
            { label: 'GitHub', href: 'https://github.com/velmphp/velm' },
            { label: 'PyVelm', href: 'https://github.com/coolsam726/pyvelm' },
          ],
        },
      ],
      copyright: `Copyright © ${new Date().getFullYear()} Velm contributors.`,
    },
    prism: {
      theme: prismThemes.oneDark,
      darkTheme: prismThemes.oneDark,
      additionalLanguages: ['php', 'bash'],
    },
  } satisfies Preset.ThemeConfig,
};

export default config;
