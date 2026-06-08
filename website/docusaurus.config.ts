import { themes as prismThemes } from 'prism-react-renderer';
import type { Config } from '@docusaurus/types';
import type * as Preset from '@docusaurus/preset-classic';

const siteUrl = process.env.DOCUSAURUS_URL ?? 'http://localhost:3000';
const baseUrl = process.env.DOCUSAURUS_BASE_URL ?? '/velm/';
/** Update when cutting a new docs version (`npm run docs:version`). */
const latestDocsVersion = '1.0.1';
const latestDocsBase = `/docs/${latestDocsVersion}`;

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
    locales: ['en', 'fr'],
    localeConfigs: {
      en: {
        label: 'English',
        direction: 'ltr',
        htmlLang: 'en-US',
      },
      fr: {
        label: 'Français',
        direction: 'ltr',
        htmlLang: 'fr-FR',
      },
    },
  },
  presets: [
    [
      'classic',
      {
        docs: {
          routeBasePath: 'docs',
          sidebarPath: './sidebars.ts',
          editUrl: 'https://github.com/velmphp/velm/tree/main/website/',
          lastVersion: '1.0.1',
          versions: {
            current: {
              label: 'Next',
              path: 'next',
              banner: 'unreleased',
            },
            '1.0.1': {
              label: '1.0.1',
              path: '1.0.1',
              banner: 'none',
            },
            '1.0.0': {
              label: '1.0.0',
              path: '1.0.0',
              banner: 'unmaintained',
            },
            '1.0.0-rc3': {
              label: '1.0.0-rc3',
              path: '1.0.0-rc3',
              banner: 'unmaintained',
            },
            '1.0.0-rc2': {
              label: '1.0.0-rc2',
              path: '1.0.0-rc2',
              banner: 'unmaintained',
            },
          },
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
          type: 'docsVersionDropdown',
          position: 'right',
          dropdownActiveClassDisabled: true,
        },
        {
          type: 'localeDropdown',
          position: 'right',
        },
        {
          href: 'https://github.com/velmphp/velm',
          label: 'GitHub',
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
            { label: 'Introduction', to: `${latestDocsBase}/intro` },
            { label: 'Installation', to: `${latestDocsBase}/guides/installation` },
            { label: 'Extending models', to: `${latestDocsBase}/models/extending-a-model` },
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
