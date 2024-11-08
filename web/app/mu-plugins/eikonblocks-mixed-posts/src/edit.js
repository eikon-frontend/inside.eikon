import { withSelect } from '@wordpress/data';
import { useBlockProps } from '@wordpress/block-editor';
import { FormTokenField } from '@wordpress/components';
import { Fragment, useState, useEffect } from '@wordpress/element';

function Edit(props) {
  const { attributes, setAttributes, availableCPTs, availableTaxonomies, availableTerms } = props;
  const { selectedCPTsData = {} } = attributes;

  const [cptSuggestions, setCptSuggestions] = useState([]);
  const [taxonomySuggestions, setTaxonomySuggestions] = useState({});
  const [termSuggestions, setTermSuggestions] = useState({});

  useEffect(() => {
    setCptSuggestions(availableCPTs.map((cpt) => cpt.slug));
  }, [availableCPTs]);

  useEffect(() => {
    const newTaxonomySuggestions = {};
    Object.keys(selectedCPTsData).forEach((cptName) => {
      newTaxonomySuggestions[cptName] = (availableTaxonomies[cptName] || []).map((taxonomy) => taxonomy.slug);
    });
    setTaxonomySuggestions(newTaxonomySuggestions);
  }, [availableTaxonomies, selectedCPTsData]);

  useEffect(() => {
    const newTermSuggestions = {};
    Object.entries(selectedCPTsData).forEach(([cptName, cptData]) => {
      Object.keys(cptData.taxonomies).forEach((taxonomyName) => {
        newTermSuggestions[`${cptName}-${taxonomyName}`] = (availableTerms[cptName][taxonomyName] || []).map((term) => term.slug);
      });
    });
    setTermSuggestions(newTermSuggestions);
  }, [availableTerms, selectedCPTsData]);

  // Handler for selecting CPTs
  const handleCPTChange = (tokens) => {
    const updatedCPTsData = {};
    tokens.forEach((cpt) => {
      updatedCPTsData[cpt] = selectedCPTsData[cpt] || { taxonomies: {} };
    });
    setAttributes({ selectedCPTsData: updatedCPTsData });
  };

  // Handler for selecting taxonomies and terms for each CPT
  const handleTaxonomyTermChange = (cptName, taxonomyName, tokens) => {
    const updatedCPTsData = { ...selectedCPTsData };
    if (!updatedCPTsData[cptName].taxonomies) {
      updatedCPTsData[cptName].taxonomies = {};
    }
    updatedCPTsData[cptName].taxonomies[taxonomyName] = tokens;
    setAttributes({ selectedCPTsData: updatedCPTsData });
  };

  return (
    <div {...useBlockProps()}>
      <div className='eikonblock-title'>eikonblock // mixed posts</div>
      <div>
        <FormTokenField
          label="Select Post Types:"
          value={Object.keys(selectedCPTsData)}
          suggestions={cptSuggestions}
          onChange={handleCPTChange}
        />
      </div>
      {Object.entries(selectedCPTsData).map(([cptName, cptData]) => (
        <div className="cpt-card" key={cptName} style={{ backgroundColor: "white", borderRadius: "8px", padding: "16px", marginBottom: "16px" }}>
          <Fragment>
            <div>
              <strong>{availableCPTs.find((cpt) => cpt.slug === cptName)?.name}</strong>
            </div>
            <FormTokenField
              label="Select Taxonomies:"
              value={Object.keys(cptData.taxonomies)}
              suggestions={taxonomySuggestions[cptName] || []}
              onChange={(tokens) => {
                const updatedCPTsData = { ...selectedCPTsData };
                updatedCPTsData[cptName].taxonomies = {};
                tokens.forEach((taxonomy) => {
                  updatedCPTsData[cptName].taxonomies[taxonomy] =
                    cptData.taxonomies[taxonomy] || [];
                });
                setAttributes({ selectedCPTsData: updatedCPTsData });
              }}
            />
            {Object.entries(cptData.taxonomies).map(([taxonomyName, terms]) => (
              <Fragment key={taxonomyName}>
                <div>
                  <strong>{availableTaxonomies[cptName]
                    ?.find((tax) => tax.slug === taxonomyName)
                    ?.name}</strong>
                </div>
                <FormTokenField
                  label={`Select Terms for ${availableTaxonomies[cptName]
                    ?.find((tax) => tax.slug === taxonomyName)
                    ?.name
                    }:`}
                  value={terms}
                  suggestions={termSuggestions[`${cptName}-${taxonomyName}`] || []}
                  onChange={(tokens) =>
                    handleTaxonomyTermChange(
                      cptName,
                      taxonomyName,
                      tokens
                    )
                  }
                />
              </Fragment>
            ))}
          </Fragment>
        </div>
      ))}
      <div className="mixed-posts" data-cpt={JSON.stringify(selectedCPTsData)}></div>
    </div>
  );
}

export default withSelect((select) => {
  // Define a static array of post types
  const availableCPTs = [
    { slug: 'post', name: 'Article' },
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
