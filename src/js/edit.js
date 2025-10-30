import { __ } from "@wordpress/i18n";
import { InspectorControls, useBlockProps, RichText } from "@wordpress/block-editor";
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	__experimentalNumberControl as NumberControl,
} from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import ServerSideRender from "@wordpress/server-side-render";

const TAX_ALLOWED = ["projekt", "post", "erleben"];
const VIEW_ALLOWED = ["kompakt", "ausfuhrlich"];


/* =============================================================== *\
   Rekursiv in allen Ebenen nach Block A suchen
\* =============================================================== */
function findBlockDeep(blocks, name) {
  for (const b of blocks) {
    if (b.name === name) return b;
    if (b.innerBlocks?.length) {
      const found = findBlockDeep(b.innerBlocks, name);
      if (found) return found;
    }
  }
  return null;
}

function useLinkedProjectId() {
  return useSelect((select) => {
    const be = select('core/block-editor');
    const ed = select('core/editor');

    // 1) Primär: aus dem Post-Meta lesen (verlässlich im Editor)
    const meta = ed.getEditedPostAttribute('meta') || {};
    const fromMeta = meta.ud_projekt_verknuepfen; // -> 1043 bei dir

    // 2) Fallback: wenn Meta nicht gesetzt, Block A (rekursiv) ansehen
    let fromBlock = null;
    if (!fromMeta) {
      const blockA = findBlockDeep(be.getBlocks(), 'ud/projekt-verknuepfen');
      fromBlock = blockA?.attributes?.projectId;
    }

    const val = Number(fromMeta ?? fromBlock);
    return Number.isFinite(val) && val > 0 ? val : null;
  });
}


export default function Edit({ attributes, setAttributes }) {
	const { title, taxonomie, nurVerknuepfteMagazinBeitraege, loop, vorschau } =
		attributes;

	// --- Sanitize für SSR ---
	const safe = {
		taxonomie: TAX_ALLOWED.includes(taxonomie) ? taxonomie : "projekt",
		nurVerknuepfteMagazinBeitraege: !!nurVerknuepfteMagazinBeitraege, 
		loop:
			Number.isFinite(Number(loop)) && Number(loop) > 0
				? Number(loop)
				: 6,
		vorschau: VIEW_ALLOWED.includes(vorschau) ? vorschau : "ausfuhrlich",
	};
	// Aktuellen Post-Typ holen
	const postType = useSelect((select) =>
		select("core/editor").getCurrentPostType(),
	);
	const blockProps = useBlockProps();


	// Verknüpfte Projekt-ID aus Block A (egal wo verschachtelt)
const linkedProjectId = useLinkedProjectId();
const hasLinkedProject = !!linkedProjectId;

	return (
		<div {...blockProps}>
            {/* Überschrift direkt im Block */}
            <RichText
                tagName="h2"
                placeholder={__("Überschrift", "ud-loop-block")}
                value={title}
                onChange={(val) => setAttributes({ title: val })}
                allowedFormats={[]} // kein Bold/Italic → nur Text
				className="above_loop"
            />

			<InspectorControls>
				<PanelBody
					title={__("Loop Einstellungen", "ud-loop-block")}
					initialOpen
				>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__("Ausgabe-Typ", "ud-loop-block")}
						value={safe.taxonomie}
						options={[
							{
								label: __("Projekte", "ud-loop-block"),
								value: "projekt",
							},
							{
								label: __("Magazin", "ud-loop-block"),
								value: "post",
							},
							{
								label: __("Muota erleben", "ud-loop-block"),
								value: "erleben",
							},
						]}
						onChange={(value) =>
							setAttributes({ taxonomie: value })
						}
					/>

					{postType === "projekt" && taxonomie === "post" && (
						<ToggleControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label="Nur mit diesem Projekt verknüpfte Beiträge ausgeben"
							checked={
								!!attributes.nurVerknuepfteMagazinBeitraege
							}
							onChange={(val) =>
								setAttributes({
									nurVerknuepfteMagazinBeitraege: !!val,
								})
							}
						/>
					)}

{ safe.taxonomie === 'post' && (hasLinkedProject || postType?.startsWith("wp_template")) && (
  <ToggleControl
    __next40pxDefaultSize
    __nextHasNoMarginBottom
    label="Nur Magazin-Beiträge zum verknüpften Projekt"
    help="Filtert auf Magazin-Beiträge, die einem Projekt zugeordnet sind."
    checked={ !!safe.nurVerknuepfteMagazinBeitraege }
    onChange={(v) => setAttributes({ nurVerknuepfteMagazinBeitraege: !!v })}
  />
)}

					<NumberControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__("Anzahl Beiträge", "ud-loop-block")}
						value={safe.loop}
						onChange={(value) =>
							setAttributes({
								loop:
									Number.isFinite(Number(value)) &&
									Number(value) > 0
										? Number(value)
										: 1,
							})
						}
						min={1}
						max={99}
					/>

					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__("Vorschau-Typ", "ud-loop-block")}
						value={safe.vorschau}
						options={[
							{
								label: __("Kompakt", "ud-loop-block"),
								value: "kompakt",
							},
							{
								label: __("Ausführlich", "ud-loop-block"),
								value: "ausfuhrlich",
							},
						]}
						onChange={(value) => setAttributes({ vorschau: value })}
					/>
				</PanelBody>
			</InspectorControls>

			{/* SSR: nur sanitisierte Werte übergeben */}
			<ServerSideRender block="ud/loop-block-for-ebs" attributes={safe} />
		</div>
	);
}
