import Link from '@docusaurus/Link';
import useBaseUrl from '@docusaurus/useBaseUrl';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import Layout from '@theme/Layout';
import Heading from '@theme/Heading';
import clsx from 'clsx';

import styles from './index.module.css';

type FeatureItem = {
  title: string;
  icon: string;
  description: string;
};

const features: FeatureItem[] = [
  {
    icon: '🧩',
    title: 'Composition-first modules',
    description:
      'Extend models and views from independent addons without subclass chains or cross-module imports.',
  },
  {
    icon: '🔁',
    title: 'Extension order & super()',
    description:
      'Explicit method resolution order (MRO) across modules, with static::super() to chain behavior without PHP subclass chains.',
  },
  {
    icon: '📦',
    title: 'Odoo-like ORM',
    description:
      'Logical model names, recordsets, manifests, and additive schema — familiar ERP patterns on Laravel.',
  },
  {
    icon: '🖥️',
    title: 'Filament admin shell',
    description:
      'List and form views driven by arch JSON, with search, quick-create, and view inheritance.',
  },
  {
    icon: '🏗️',
    title: 'Zero coupling between addons',
    description:
      'Each module declares depends() and $inherit; the loader merges behavior at install time.',
  },
  {
    icon: '🐍',
    title: 'PyVelm-aligned',
    description:
      'Concepts map to the Python reference implementation — same mental model, PHP-native APIs.',
  },
];

function HomepageHeader() {
  const {siteConfig} = useDocusaurusContext();
  const logoSrc = useBaseUrl('/img/logo-concept.svg');

  return (
    <header className={clsx('hero', styles.heroBanner)}>
      <div className="container">
        <div className={styles.heroInner}>
          <div className={styles.heroCopy}>
            <Heading as="h1" className={styles.heroTitle}>
              <span className={styles.wordmark}>
                <span className={styles.brandPrimary}>Velm</span>
                <sup className={styles.phpSup} aria-label="PHP">
                  php
                </sup>
              </span>
            </Heading>
            <p className={styles.heroTagline}>{siteConfig.tagline}</p>
            <p className={styles.heroSubtitle}>
              Odoo semantics on Laravel — extensibility without inheritance chains.
            </p>
            <div className={styles.buttons}>
              <Link className="button button--primary button--lg" to="/docs/guides/installation">
                Get started
              </Link>
              <Link className="button button--secondary button--lg" to="/docs/intro">
                Read the docs
              </Link>
            </div>
          </div>
          <div className={styles.heroVisual}>
            <div className={styles.heroGlow} aria-hidden />
            <img
              src={logoSrc}
              alt="Velm"
              className={styles.heroLogo}
              width={320}
              height={320}
            />
          </div>
        </div>
      </div>
    </header>
  );
}

function Feature({title, icon, description}: FeatureItem) {
  return (
    <div className={clsx('col col--4', styles.feature)}>
      <div className={styles.featureIcon} aria-hidden>
        {icon}
      </div>
      <Heading as="h3">{title}</Heading>
      <p>{description}</p>
    </div>
  );
}

export default function Home() {
  const {siteConfig} = useDocusaurusContext();

  return (
    <Layout title={siteConfig.title} description={siteConfig.tagline}>
      <HomepageHeader />
      <main>
        <section className={styles.features}>
          <div className="container">
            <div className="row">
              {features.map((props) => (
                <Feature key={props.title} {...props} />
              ))}
            </div>
          </div>
        </section>
      </main>
    </Layout>
  );
}
