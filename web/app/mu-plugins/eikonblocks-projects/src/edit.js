import { withSelect } from '@wordpress/data';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { FormTokenField } from '@wordpress/components'; // Ajouté pour les champs à tags

function Edit(props) {
  const { attributes, setAttributes, years, sections } = props;
  const { selectedYears = [], selectedSections = [] } = attributes; // Modifié pour des tableaux

  const handleYearsChange = (values) => {
    setAttributes({ selectedYears: values });
  };

  const handleSectionsChange = (values) => {
    setAttributes({ selectedSections: values });
  };

  return (
    <div {...useBlockProps()}>
      <div className='eikonblock-title'>eikonblock // projects</div>
      <FormTokenField
        label="Sélectionnez l'année:"
        value={selectedYears}
        suggestions={years ? years.map((year) => year.name) : []}
        onChange={handleYearsChange}
      />
      <FormTokenField
        label="Sélectionnez la section:"
        value={selectedSections}
        suggestions={sections ? sections.map((section) => section.name) : []}
        onChange={handleSectionsChange}
      />
      <div
        className="wp-block-eikonblocks-projects"
        data-year={selectedYears.join(',')}
        data-section={selectedSections.join(',')}
      ></div>
    </div>
  );
}

export default withSelect((select) => {
  const { getEntityRecords } = select("core");

  const years = getEntityRecords("taxonomy", "year", { per_page: -1 });
  const sections = getEntityRecords("taxonomy", "section", { per_page: -1 });

  return { years, sections };
})(Edit);
