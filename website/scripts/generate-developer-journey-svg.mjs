/**
 * Generate Excalidraw-style SVG using roughjs (same engine Excalidraw uses).
 */
import { writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import rough from 'roughjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const out = join(__dirname, '../static/img/developer-journey.svg');

const W = 1000;
const H = 440;

const rc = rough.generator({
  roughness: 1.15,
  bowing: 1.2,
  stroke: '#1e1e1e',
  strokeWidth: 2,
  fillStyle: 'solid',
});

function paths(drawable) {
  const stroke = drawable.options.stroke ?? '#1e1e1e';
  const width = drawable.options.strokeWidth ?? 2;

  return drawable.sets
    .filter((s) => s.type === 'path')
    .map((s) => `<path d="${s.ops.map((op) => {
      if (op.op === 'move') return `M ${op.data[0]} ${op.data[1]}`;
      if (op.op === 'bcurveTo') return `C ${op.data[0]} ${op.data[1]} ${op.data[2]} ${op.data[3]} ${op.data[4]} ${op.data[5]}`;
      if (op.op === 'lineTo') return `L ${op.data[0]} ${op.data[1]}`;
      return '';
    }).join(' ')}" fill="${drawable.options.fill ?? 'none'}" stroke="${stroke}" stroke-width="${width}" stroke-linecap="round" stroke-linejoin="round"/>`)
    .join('\n    ');
}

function sketchRect(x, y, w, h, fill, r = 8) {
  return paths(rc.rectangle(x, y, w, h, { fill, roughness: 1.1, bowing: 1, fillStyle: 'solid' }));
}

function sketchCircle(cx, cy, r, fill = '#ffffff') {
  return paths(rc.circle(cx, cy, r * 2, { fill, roughness: 1.2 }));
}

function sketchLine(x1, y1, x2, y2) {
  return paths(rc.line(x1, y1, x2, y2, { roughness: 1.3, bowing: 1.4 }));
}

function sketchArrow(x1, y1, x2, y2) {
  const line = paths(rc.line(x1, y1, x2, y2, { roughness: 1.2, bowing: 1 }));
  const head = paths(rc.polygon([
    [x2, y2],
    [x2 - 12, y2 - 5],
    [x2 - 12, y2 + 5],
  ], { fill: '#1e1e1e', stroke: '#1e1e1e', roughness: 0.8 }));
  return line + head;
}

function sketchPathD(d, stroke = '#ced4da', fill = 'none', dash = '') {
  const drawable = rc.path(d, { stroke, fill, roughness: 1.4, bowing: 1.5, strokeWidth: dash ? 2 : 2 });
  const dashAttr = dash ? ` stroke-dasharray="${dash}"` : '';
  return drawable.sets
    .filter((s) => s.type === 'path')
    .map((s) => `<path d="${s.ops.map((op) => {
      if (op.op === 'move') return `M ${op.data[0]} ${op.data[1]}`;
      if (op.op === 'bcurveTo') return `C ${op.data[0]} ${op.data[1]} ${op.data[2]} ${op.data[3]} ${op.data[4]} ${op.data[5]}`;
      if (op.op === 'lineTo') return `L ${op.data[0]} ${op.data[1]}`;
      return '';
    }).join(' ')}" fill="${fill}" stroke="${stroke}" stroke-width="2" stroke-linecap="round"${dashAttr}/>`)
    .join('\n    ');
}

const svg = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" role="img" aria-labelledby="title desc">
  <title id="title">Velm developer journey: start, code, ship</title>
  <desc id="desc">Hand-drawn Excalidraw-style guide for developers: install, author modules, deploy on Laravel.</desc>
  <defs>
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Caveat:wght@500;700&amp;family=Patrick+Hand&amp;display=swap');
      .hand { font-family: 'Caveat', 'Patrick Hand', cursive, sans-serif; fill: #1e1e1e; }
      .title { font-size: 28px; font-weight: 700; }
      .sub { font-size: 16px; fill: #495057; }
      .label { font-size: 22px; font-weight: 700; }
      .small { font-size: 15px; }
      .mono { font-family: 'Patrick Hand', cursive; font-size: 13px; }
      .tiny { font-size: 13px; font-weight: 600; }
      .foot { font-size: 14px; fill: #868e96; }
    </style>
  </defs>

  <rect width="${W}" height="${H}" fill="#ffffff"/>

  <text x="500" y="42" text-anchor="middle" class="hand title">Your path: start → code → ship</text>
  <text x="500" y="68" text-anchor="middle" class="hand sub">One Laravel app · PHP modules · working admin UI</text>

  <!-- dashed journey -->
  ${sketchPathD('M 70 210 Q 210 175 350 210 T 630 210 T 900 210', '#adb5bd', 'none', '10 8')}

  <!-- panel 1 START -->
  <g>
    ${sketchRect(30, 95, 270, 285, '#a5d8ff')}
    <text x="165" y="135" text-anchor="middle" class="hand label">1. Start</text>
    <text x="165" y="158" text-anchor="middle" class="hand small" fill="#495057">~5 minutes</text>
    ${sketchRect(55, 175, 220, 95, '#343a40')}
    <circle cx="72" cy="192" r="5" fill="#fa5252" stroke="none"/>
    <circle cx="90" cy="192" r="5" fill="#fab005" stroke="none"/>
    <circle cx="108" cy="192" r="5" fill="#40c057" stroke="none"/>
    <text x="68" y="222" class="hand mono" fill="#a5d8ff">$ composer create-project</text>
    <text x="68" y="242" class="hand mono" fill="#a5d8ff">velmphp/app my_app</text>
    <text x="68" y="262" class="hand mono" fill="#b2f2bb">$ composer run setup</text>
    ${sketchRect(120, 285, 90, 55, '#ffffff')}
    ${sketchRect(128, 293, 74, 38, '#a5d8ff')}
    ${paths(rc.polygon([[105, 345], [225, 345], [238, 360], [92, 360]], { fill: '#dee2e6', roughness: 1 }))}
    <text x="165" y="375" text-anchor="middle" class="hand small">open /velm — panel ready ☕</text>
  </g>

  ${sketchArrow(308, 230, 338, 230)}

  <!-- panel 2 CODE -->
  <g>
    ${sketchRect(345, 95, 310, 285, '#b2f2bb')}
    <text x="500" y="135" text-anchor="middle" class="hand label">2. Code</text>
    <text x="500" y="158" text-anchor="middle" class="hand small" fill="#495057">addons/my_module/</text>
    <text x="375" y="188" class="hand mono">├── __velm__.php</text>
    <text x="375" y="208" class="hand mono">├── models/Product.php</text>
    <text x="375" y="228" class="hand mono">└── views/product.php</text>
    ${sketchRect(375, 245, 70, 28, '#ffffff')}
    <text x="410" y="265" text-anchor="middle" class="hand mono">ListView</text>
    ${sketchRect(455, 245, 70, 28, '#ffffff')}
    <text x="490" y="265" text-anchor="middle" class="hand mono">FormView</text>
    ${sketchRect(535, 245, 58, 28, '#ffffff')}
    <text x="564" y="265" text-anchor="middle" class="hand mono">menus</text>
    ${sketchRect(375, 285, 255, 36, '#fff3bf')}
    <text x="502" y="310" text-anchor="middle" class="hand mono">velm:module:sync my_module</text>
    <text x="500" y="360" text-anchor="middle" class="hand small">lists + forms appear — no hand-built CRUD</text>
  </g>

  ${sketchArrow(663, 230, 693, 230)}

  <!-- panel 3 SHIP -->
  <g>
    ${sketchRect(700, 95, 270, 285, '#fff3bf')}
    <text x="835" y="135" text-anchor="middle" class="hand label">3. Ship</text>
    <text x="835" y="158" text-anchor="middle" class="hand small" fill="#495057">same Laravel deploy</text>
    ${sketchRect(735, 180, 200, 34, '#ffffff')}
    <text x="835" y="203" text-anchor="middle" class="hand small">Forge · Docker · VPS</text>
    ${sketchRect(735, 222, 200, 34, '#ffffff')}
    <text x="835" y="245" text-anchor="middle" class="hand mono">velm:migrate</text>
    ${sketchRect(735, 264, 200, 34, '#a5d8ff')}
    <text x="835" y="287" text-anchor="middle" class="hand small">/velm live in prod 🚀</text>
    ${paths(rc.polygon([[920, 195], [945, 170], [952, 195], [945, 208]], { fill: '#ffc9c9', roughness: 1.2 }))}
    ${sketchCircle(775, 335, 14, '#ffc9c9')}
    ${sketchCircle(835, 335, 14, '#b2f2bb')}
    ${sketchCircle(895, 335, 14, '#d0bfff')}
    <text x="835" y="375" text-anchor="middle" class="hand small">modules per database</text>
  </g>

  <!-- stick figure YOU on path -->
  <g transform="translate(490, 168)">
    ${sketchCircle(0, 0, 12)}
    ${sketchLine(-3, 14, 3, 14)}
    ${sketchLine(0, 12, 0, 42)}
    ${sketchLine(0, 22, -18, 38)}
    ${sketchLine(0, 22, 20, 30)}
    ${sketchLine(0, 42, -15, 62)}
    ${sketchLine(0, 42, 18, 58)}
    ${sketchRect(18, 26, 24, 18, '#a5d8ff', 4)}
    ${sketchPathD('M -32 58 Q -22 54 -14 58', '#1e1e1e')}
    <text x="0" y="82" text-anchor="middle" class="hand tiny">you</text>
  </g>

  <!-- milestone stick: coffee -->
  <g transform="translate(120, 340)">
    ${sketchCircle(0, 0, 8)}
    ${sketchLine(0, 8, 0, 26)}
    ${sketchLine(0, 14, -10, 22)}
    ${sketchLine(0, 14, 12, 12)}
    ${sketchLine(0, 26, -8, 40)}
    ${sketchLine(0, 26, 8, 40)}
    ${sketchRect(14, 8, 10, 12, '#ffec99')}
  </g>

  <!-- milestone stick: celebrate -->
  <g transform="translate(880, 340)">
    ${sketchCircle(0, 0, 8)}
    ${sketchLine(0, 8, 0, 26)}
    ${sketchLine(0, 14, -14, 0)}
    ${sketchLine(0, 14, 14, 0)}
    ${sketchLine(0, 26, -7, 40)}
    ${sketchLine(0, 26, 7, 40)}
  </g>

  <text x="500" y="420" text-anchor="middle" class="hand foot">velm:make:* · $inherit · ship on Laravel</text>
</svg>
`;

writeFileSync(out, svg);
console.log('Wrote', out);
