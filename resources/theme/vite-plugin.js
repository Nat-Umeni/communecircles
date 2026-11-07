import fs from 'node:fs';
import path from 'node:path';

const HEADER = '/* Generated from resources/theme/tokens.json by resources/theme/vite-plugin.js. Edit tokens.json, not this file. */';

/**
 * Turn the design tokens JSON into Tailwind v4 CSS:
 * raw palette, radii and fonts go in @theme, and the light/dark semantic colours
 * become CSS variables that @theme inline maps to utilities like bg-primary.
 */
export function tokensToCss(tokens) {
    const resolve = (value) => {
        const alias = typeof value === 'string' && value.match(/^\{(.+)\}$/);
        if (!alias) {
            return value;
        }
        const target = alias[1].split('.').reduce((node, key) => node?.[key], tokens);
        if (target?.$value === undefined) {
            throw new Error(`Unknown token reference ${value}`);
        }
        return resolve(target.$value);
    };

    // Yields [name, token] for every token below node, with names joined by dashes (forest.900 -> forest-900).
    const flatten = function* (node, prefix = []) {
        for (const [key, child] of Object.entries(node)) {
            if (key.startsWith('$')) {
                continue;
            }
            if (child.$value !== undefined) {
                yield [[...prefix, key].join('-'), child];
            } else {
                yield* flatten(child, [...prefix, key]);
            }
        }
    };

    const fontStack = (fonts) => fonts.map((font) => (/\s/.test(font) ? `'${font}'` : font)).join(', ');
    const declarations = (entries, indent = '    ') => entries.map(([name, value]) => `${indent}--${name}: ${value};`).join('\n');

    const theme = [
        ...[...flatten(tokens.color)].map(([name, token]) => [`color-${name}`, resolve(token.$value)]),
        ...[...flatten(tokens.radius)].map(([name, token]) => [`radius-${name}`, resolve(token.$value)]),
        ...[...flatten(tokens.font)].map(([name, token]) => [`font-${name}`, fontStack(resolve(token.$value))]),
    ];
    const light = [...flatten(tokens.theme.light)].map(([name, token]) => [name, resolve(token.$value)]);
    const dark = [...flatten(tokens.theme.dark)].map(([name, token]) => [name, resolve(token.$value)]);
    const semantic = light.map(([name]) => [`color-${name}`, `var(--${name})`]);

    return [
        HEADER,
        `@theme {\n${declarations(theme)}\n}`,
        `:root {\n${declarations(light)}\n}`,
        `.dark {\n${declarations(dark)}\n}`,
        `@theme inline {\n${declarations(semantic)}\n}`,
        '',
    ].join('\n\n');
}

function writeTokensCss(inputPath, outputPath) {
    const css = tokensToCss(JSON.parse(fs.readFileSync(inputPath, 'utf8')));
    if (!fs.existsSync(outputPath) || fs.readFileSync(outputPath, 'utf8') !== css) {
        fs.writeFileSync(outputPath, css);
    }
}

/**
 * Regenerates resources/css/tokens.css when the build starts and whenever tokens.json changes in dev.
 */
export default function themeTokens({ input = 'resources/theme/tokens.json', output = 'resources/css/tokens.css' } = {}) {
    let inputPath;
    let outputPath;

    return {
        name: 'commune:theme-tokens',
        configResolved(config) {
            inputPath = path.resolve(config.root, input);
            outputPath = path.resolve(config.root, output);
        },
        buildStart() {
            this.addWatchFile(inputPath);
            writeTokensCss(inputPath, outputPath);
        },
        configureServer(server) {
            server.watcher.add(inputPath);
            server.watcher.on('change', (file) => {
                if (path.resolve(file) !== inputPath) {
                    return;
                }
                try {
                    writeTokensCss(inputPath, outputPath);
                } catch (error) {
                    server.config.logger.error(`[theme-tokens] ${error.message}`);
                }
            });
        },
    };
}
