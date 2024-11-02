import { withSelect } from '@wordpress/data';
import { useBlockProps } from '@wordpress/block-editor';
import { SelectControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';

function Edit(props) {
  const { attributes, setAttributes, availableCPTs, availableTaxonomies, availableTerms } = props;
  const { selectedCPTsData = {} } = attributes;

  // Handler for selecting CPTs
  const handleCPTChange = (selectedCPTs) => {
    const updatedCPTsData = {};
    selectedCPTs.forEach((cpt) => {
      updatedCPTsData[cpt] = selectedCPTsData[cpt] || { taxonomies: {} };
    });
    setAttributes({ selectedCPTsData: updatedCPTsData });
  };

  // Handler for selecting taxonomies and terms for each CPT
  const handleTaxonomyTermChange = (cptName, taxonomyName, selectedTerms) => {
    const updatedCPTsData = { ...selectedCPTsData };
    if (!updatedCPTsData[cptName].taxonomies) {
      updatedCPTsData[cptName].taxonomies = {};
    }
    updatedCPTsData[cptName].taxonomies[taxonomyName] = selectedTerms;
    setAttributes({ selectedCPTsData: updatedCPTsData });
  };

  return (
    <div {...useBlockProps()}>
      <div className='eikonblock-title'>eikonblock // mixed posts</div>
      <div>
        <SelectControl
          multiple
          label="Select Post Types:"
          value={Object.keys(selectedCPTsData)}
          options={availableCPTs.map((cpt) => ({ label: cpt.name, value: cpt.slug }))}
          onChange={handleCPTChange}
        />
      </div>
      {Object.entries(selectedCPTsData).map(([cptName, cptData]) => (
        <Fragment key={cptName}>
          <h4>{cptName}</h4>
          <SelectControl
            multiple
            label="Select Taxonomies:"
            value={Object.keys(cptData.taxonomies)}
            options={(availableTaxonomies[cptName] || []).map((taxonomy) => ({
              label: taxonomy.name,
              value: taxonomy.slug,
            }))}
            onChange={(selectedTaxonomies) => {
              const updatedCPTsData = { ...selectedCPTsData };
              updatedCPTsData[cptName].taxonomies = {};
              selectedTaxonomies.forEach((taxonomy) => {
                updatedCPTsData[cptName].taxonomies[taxonomy] = cptData.taxonomies[taxonomy] || [];
              });
              setAttributes({ selectedCPTsData: updatedCPTsData });
            }}
          />
          {Object.entries(cptData.taxonomies).map(([taxonomyName, terms]) => (
            <Fragment key={taxonomyName}>
              <h5>{taxonomyName}</h5>
              <SelectControl
                multiple
                label={`Select Terms for ${taxonomyName}:`}
                value={terms}
                options={(availableTerms[cptName][taxonomyName] || []).map((term) => ({
                  label: term.name,
                  value: term.slug,
                }))}
                onChange={(selectedTerms) => handleTaxonomyTermChange(cptName, taxonomyName, selectedTerms)}
              />
            </Fragment>
          ))}
        </Fragment>
      ))}
      <div className="mixed-posts" data-cpt={JSON.stringify(selectedCPTsData)}></div>
    </div>
  );
}

export default withSelect((select) => {
  // Define a static array of post types
  const availableCPTs = [
    { slug: 'post', name: 'Post' },
    { slug: 'project', name: 'Project' },
  ];

  const { getTaxonomies, getEntityRecords } = select('core');

  const availableTaxonomies = {};
  const availableTerms = {};

  availableCPTs.forEach((postType) => {
    const cptSlug = postType.slug;
    const taxonomies = getTaxonomies({ type: cptSlug }) || [];
    availableTaxonomies[cptSlug] = taxonomies;

    availableTerms[cptSlug] = {};
    taxonomies.forEach((taxonomy) => {
      const taxonomySlug = taxonomy.slug;
      const terms = getEntityRecords('taxonomy', taxonomySlug, { per_page: -1 }) || [];
      availableTerms[cptSlug][taxonomySlug] = terms;
    });
  });

  return {
    availableCPTs,
    availableTaxonomies,
    availableTerms,
  };
})(Edit);
