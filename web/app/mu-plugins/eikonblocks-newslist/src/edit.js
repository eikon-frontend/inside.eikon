import { useSelect } from '@wordpress/data';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { FormTokenField, PanelBody, RangeControl, SelectControl, Spinner } from '@wordpress/components';
import { useMemo } from '@wordpress/element';

// Normalize accents for accent-insensitive comparison
const normalizeString = (str) => {
  if (!str) return '';
  return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
};

export default function Edit({ attributes, setAttributes }) {
  const { selectedTaxonomies = {}, postsPerPage = 10, orderBy = 'date', orderDirection = 'DESC' } = attributes;

  // Fetch taxonomies for post type
  const taxonomies = useSelect((select) => {
    return select('core').getTaxonomies({ type: 'post' }) || [];
  }, []);

  // Fetch all terms for each taxonomy
  const allTerms = useSelect((select) => {
    const result = {};
    taxonomies.forEach((taxonomy) => {
      result[taxonomy.slug] = select('core').getEntityRecords('taxonomy', taxonomy.slug, { per_page: -1 }) || [];
    });
    return result;
  }, [taxonomies]);

  // Fetch posts for preview
  const allPosts = useSelect((select) => {
    return select('core').getEntityRecords('postType', 'post', { per_page: 100 });
  }, []);

  // Taxonomy slug suggestions
  const taxonomySuggestions = useMemo(() => {
    return taxonomies.map((t) => t.slug);
  }, [taxonomies]);

  // Filter preview posts based on selected taxonomies/terms
  const previewPosts = useMemo(() => {
    if (!allPosts) return undefined;

    const hasFilters = Object.entries(selectedTaxonomies).some(
      ([, terms]) => terms.length > 0
    );

    if (!hasFilters) return allPosts;

    return allPosts.filter((post) => {
      for (const [taxonomySlug, termNames] of Object.entries(selectedTaxonomies)) {
        if (termNames.length === 0) continue;

        const termsInPost = post[taxonomySlug] || [];

        const hasMatch = termNames.some((termName) => {
          const termsForTax = allTerms[taxonomySlug] || [];
          const matchingTerm = termsForTax.find(
            (t) => normalizeString(t.name) === normalizeString(termName)
          );
          return matchingTerm && termsInPost.includes(matchingTerm.id);
        });

        if (hasMatch) return true;
      }
      return false;
    });
  }, [allPosts, allTerms, selectedTaxonomies]);

  // Handle taxonomy selection
  const handleTaxonomyChange = (tokens) => {
    const updatedTaxonomies = {};
    tokens.forEach((slug) => {
      updatedTaxonomies[slug] = selectedTaxonomies[slug] || [];
    });
    setAttributes({ selectedTaxonomies: updatedTaxonomies });
  };

  // Handle term selection for a specific taxonomy
  const handleTermChange = (taxonomySlug, tokens) => {
    setAttributes({
      selectedTaxonomies: {
        ...selectedTaxonomies,
        [taxonomySlug]: tokens,
      },
    });
  };

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Paramètres de la liste d\'actualités', 'eikonblocks')}>
          <RangeControl
            label={__('Nombre de publications', 'eikonblocks')}
            value={postsPerPage}
            onChange={(value) => setAttributes({ postsPerPage: value })}
            min={1}
            max={50}
          />
          <SelectControl
            label={__('Trier par', 'eikonblocks')}
            value={orderBy}
            options={[
              { label: __('Date', 'eikonblocks'), value: 'date' },
              { label: __('Titre', 'eikonblocks'), value: 'title' },
            ]}
            onChange={(value) => setAttributes({ orderBy: value })}
          />
          <SelectControl
            label={__('Direction', 'eikonblocks')}
            value={orderDirection}
            options={[
              { label: __('Décroissant', 'eikonblocks'), value: 'DESC' },
              { label: __('Croissant', 'eikonblocks'), value: 'ASC' },
            ]}
            onChange={(value) => setAttributes({ orderDirection: value })}
          />
        </PanelBody>
      </InspectorControls>
      <div {...useBlockProps()}>
        <div className="eikonblock-title">eikonblock // news list</div>

        {/* Taxonomy Selection */}
        <div className="ekn-card">
          <div className="ekn-card__main">
            <div className="ekn-field ekn-field--grow">
              <FormTokenField
                label={__('Sélectionner les taxonomies à filtrer :', 'eikonblocks')}
                value={Object.keys(selectedTaxonomies)}
                suggestions={taxonomySuggestions}
                onChange={handleTaxonomyChange}
                placeholder={__('Rechercher des taxonomies...', 'eikonblocks')}
                __experimentalShowHowTo={false}
              />
            </div>
          </div>
        </div>

        {/* Term Selection for each selected taxonomy */}
        {Object.entries(selectedTaxonomies).map(([taxonomySlug, selectedTerms]) => {
          const taxonomy = taxonomies.find((t) => t.slug === taxonomySlug);
          const taxLabel = taxonomy ? taxonomy.name : taxonomySlug;
          const termSuggestions = (allTerms[taxonomySlug] || []).map((t) => t.name);

          return (
            <div className="ekn-card" key={taxonomySlug}>
              <div className="ekn-card__header">
                <span className="ekn-label">{taxLabel}</span>
              </div>
              <div className="ekn-card__main">
                <div className="ekn-field ekn-field--grow">
                  <FormTokenField
                    label={__('Sélectionner des termes dans ', 'eikonblocks') + taxLabel + ':'}
                    value={selectedTerms}
                    suggestions={termSuggestions}
                    onChange={(tokens) => handleTermChange(taxonomySlug, tokens)}
                    placeholder={__('Rechercher dans ', 'eikonblocks') + taxLabel + '...'}
                    __experimentalShowHowTo={false}
                  />
                </div>
              </div>
            </div>
          );
        })}

        {/* Posts Preview */}
        <div className="ekn-card">
          <div className="ekn-card__header">
            <span className="ekn-label">{__('Aperçu (', 'eikonblocks')}{previewPosts ? previewPosts.length : 0}{__(' publications)', 'eikonblocks')}</span>
          </div>
          <div className="ekn-card__main">
            {previewPosts === undefined ? (
              <Spinner />
            ) : previewPosts.length > 0 ? (
              <ul style={{ maxHeight: '300px', overflowY: 'auto', margin: '0', paddingLeft: '20px', width: '100%' }}>
                {previewPosts.slice(0, postsPerPage).map((post) => (
                  <li key={post.id} style={{ marginBottom: '4px', fontSize: '14px', color: '#374151' }}>
                    {post.title.rendered || __('(Sans titre)', 'eikonblocks')}
                  </li>
                ))}
              </ul>
            ) : (
              <p style={{ fontSize: '14px', margin: '0', color: '#6b7280' }}>{__('Aucune publication ne correspond à vos filtres', 'eikonblocks')}</p>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
