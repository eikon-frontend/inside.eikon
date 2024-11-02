import { withSelect } from '@wordpress/data';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

function Edit(props) {
  const { attributes, setAttributes, years, sections } = props;
  const { selectedYear, selectedSection } = attributes;

  const handleYearChange = (event) => {
    setAttributes({ selectedYear: event.target.value });
  };

  const handleSectionChange = (event) => {
    setAttributes({ selectedSection: event.target.value });
  };

  return (
    <div {...useBlockProps()}>
      <div className='eikonblock-title'>eikonblock // projects</div>
      <table>
        <tbody>
          <tr>
            <td><label>Sélectionnez l'année:</label></td>
            <td>
              <select value={selectedYear} onChange={handleYearChange}>
                <option value="">Select Year</option>
                {years && years.map((year) => (
                  <option key={year.id} value={year.slug}>
                    {year.name}
                  </option>
                ))}
              </select>
            </td>
          </tr>
          <tr>
            <td><label>Sélectionnez la section:</label></td>
            <td>
              <select value={selectedSection} onChange={handleSectionChange}>
                <option value="">Select Section</option>
                {sections && sections.map((section) => (
                  <option key={section.id} value={section.slug}>
                    {section.name}
                  </option>
                ))}
              </select>
            </td>
          </tr>
        </tbody>
      </table>
      <div className="wp-block-eikonblocks-projects" data-year={selectedYear} data-section={selectedSection}></div>
    </div>
  );
}

export default withSelect((select) => {
  const { getEntityRecords } = select("core");

  const years = getEntityRecords("taxonomy", "year", { per_page: -1 });
  const sections = getEntityRecords("taxonomy", "section", { per_page: -1 });

  return { years, sections };
})(Edit);
