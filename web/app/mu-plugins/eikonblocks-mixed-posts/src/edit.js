import { withSelect } from '@wordpress/data';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

function Edit(props) {
  const { attributes, setAttributes, years } = props;
  const { selectedYear } = attributes;

  const handleYearChange = (event) => {
    setAttributes({ selectedYear: event.target.value });
  };

  return (
    <div {...useBlockProps()}>
      <div className='eikonblock-title'>eikonblock // mixed posts</div>
      <div>
        <label>
          Select Year:
          <select value={selectedYear} onChange={handleYearChange}>
            <option value="">Select Year</option>
            {years && years.map((year) => (
              <option key={year.id} value={year.slug}>
                {year.name}
              </option>
            ))}
          </select>
        </label>
      </div>
      <div className="mixed-posts" data-year={selectedYear}></div>
    </div>
  );
}

export default withSelect((select) => {
  const { getEntityRecords } = select("core");

  const years = getEntityRecords("taxonomy", "year", { per_page: -1 });

  return { years };
})(Edit);
