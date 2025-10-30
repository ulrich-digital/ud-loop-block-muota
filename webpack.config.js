/*
 * Erweiterte Webpack-Konfiguration für WordPress-Plugins.
 *
 * Achtung:
 * Diese Datei wird **nur verwendet**, wenn sie in der package.json explizit referenziert wird!
 *
 * Beispiel (in package.json):
 * "scripts": {
 *   "build": "webpack --config webpack.config.js",
 *   "start": "webpack --watch --config webpack.config.js"
 * }
 *
 * Ohne diese Angabe greift WordPress standardmässig auf die interne Konfiguration von @wordpress/scripts zurück –
 * und ignoriert diese Datei vollständig!
 */


const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const path = require("path");

// Entferne ursprüngliche SVG-Regel
const filteredRules = defaultConfig.module.rules.filter(
	(rule) => !rule.test?.toString().includes("svg")
);



module.exports = {
	...defaultConfig,
	entry: {
		'editor-script': path.resolve(__dirname, "src/js/editor.js"),
		'frontend-script': path.resolve(__dirname, "src/js/frontend.js"),
		'editor-style': path.resolve(__dirname, "src/css/editor.scss"),
		'frontend-style': path.resolve(__dirname, "src/css/frontend.scss"),
	},
	output: {
		path: path.resolve(__dirname, "build"),
		filename: "[name].js",
	},
	module: {
		rules: [
			...filteredRules,

			// 💡 alle SVGs als React-Komponente (kein Asset mehr nötig)
			{
				test: /\.svg$/i,
				issuer: /\.[jt]sx?$/,
				use: [
					{
						loader: '@svgr/webpack',
						options: { icon: true },
					},
				],
			},
		],
	},
};
