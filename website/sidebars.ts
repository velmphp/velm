import type {SidebarsConfig} from '@docusaurus/plugin-content-docs';

/**
 * Sidebar order follows complexity (Laravel-style): prologue → fundamentals → digging deeper.
 */
const sidebars: SidebarsConfig = {
  docs: [
    {
      type: 'category',
      label: 'Prologue',
      collapsed: false,
      items: [
        'intro',
        'guides/installation',
        'guides/migrations',
        'guides/scaffolding',
      ],
    },
    {
      type: 'category',
      label: 'Models',
      link: {type: 'doc', id: 'models/index'},
      collapsed: false,
      items: [
        'models/defining-a-model',
        'models/extending-a-model',
        'models/method-overrides-and-super',
        {
          type: 'category',
          label: 'Digging Deeper',
          collapsed: false,
          items: ['models/stacking-extensions'],
        },
      ],
    },
  ],
};

export default sidebars;
