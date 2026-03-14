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
        <PanelBody title="News List Settings">
          <RangeControl
            label="Number of posts"
            value={postsPerPage}
            onChange={(value) => setAttributes({ postsPerPage: value })}
            min={1}
            max={50}
          />
          <SelectControl
            label="Sort by"
            value={orderBy}
            options={[
              { label: 'Date', value: 'date' },
              { label: 'Title', value: 'title' },
            ]}
            onChange={(value) => setAttributes({ orderBy: value })}
          />
          <SelectControl
            label="Direction"
            value={orderDirection}
            options={[
              { label: 'Descending', value: 'DESC' },
              { label: 'Ascending', value: 'ASC' },
            ]}
            onChange={(value) => setAttributes({ orderDirection: value })}
          />
        </PanelBody>
      </InspectorControls>
      <div {...useBlockProps()}>
        <div className="eikonblock-title">eikonblock // news list</div>

        {/* Taxonomy Selection */}
        <div style={{ marginBottom: '16px' }}>
          <FormTokenField
            label="Select Taxonomies to filter:"
            value={Object.keys(selectedTaxonomies)}
            suggestions={taxonomySuggestions}
            onChange={handleTaxonomyChange}
            placeholder="Search taxonomies..."
            __experimentalShowHowTo={false}
          />
        </div>

        {/* Term Selection for each selected taxonomy */}
        {Object.entries(selectedTaxonomies).map(([taxonomySlug, selectedTerms]) => {
          const taxonomy = taxonomies.find((t) => t.slug === taxonomySlug);
          const taxLabel = taxonomy ? taxonomy.name : taxonomySlug;
          const termSuggestions = (allTerms[taxonomySlug] || []).map((t) => t.name);

          return (
            <div key={taxonomySlug}>
              <strong>{taxLabel}</strong>
              <FormTokenField
                label={`Select terms from ${taxLabel}:`}
                value={selectedTerms}
                suggestions={termSuggestions}
                onChange={(tokens) => handleTermChange(taxonomySlug, tokens)}
                placeholder={`Search ${taxLabel}...`}
                __experimentalShowHowTo={false}
              />
            </div>
          );
        })}

        {/* Posts Preview */}
        <div style={{ marginTop: '20px' }}>
          <p>
            <strong>
              Preview ({previewPosts ? previewPosts.length : 0} posts):
            </strong>
          </p>
          {previewPosts === undefined ? (
            <Spinner />
          ) : previewPosts.length > 0 ? (
            <ul style={{ maxHeight: '300px', overflowY: 'auto', margin: '8px 0', paddingLeft: '20px' }}>
              {previewPosts.slice(0, postsPerPage).map((post) => (
                <li key={post.id} style={{ marginBottom: '4px', fontSize: '14px' }}>
                  {post.title.rendered || '(Untitled)'}
                </li>
              ))}
            </ul>
          ) : (
            <p style={{ fontSize: '14px' }}>No posts match your filters</p>
          )}
        </div>
      </div>
    </>
  );
}
