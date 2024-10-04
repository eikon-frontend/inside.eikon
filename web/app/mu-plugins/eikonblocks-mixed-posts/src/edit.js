import { useState } from 'react';
import { withSelect } from '@wordpress/data';
import { useBlockProps, InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

function Edit(props) {
  const { attributes, setAttributes, years } = props;
  const { selectedYear, backgroundColor, textColor } = attributes;

  const handleYearChange = (event) => {
    setAttributes({ selectedYear: event.target.value });
  };

  return (
    <>
      <InspectorControls>
        <PanelColorSettings
          title={__('Color Settings', 'eikonblocks')}
          initialOpen={true}
          colorSettings={[
            {
              value: backgroundColor,
              onChange: (value) => setAttributes({ backgroundColor: value }),
              label: __('Background Color', 'eikonblocks'),
            },
            {
              value: textColor,
              onChange: (value) => setAttributes({ textColor: value }),
              label: __('Text Color', 'eikonblocks'),
            },
          ]}
        />
      </InspectorControls>
      <div {...useBlockProps()} style={{ backgroundColor: backgroundColor, color: textColor }}>
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
    </>
  );
}

export default withSelect((select) => {
  const { getEntityRecords } = select("core");

  const years = getEntityRecords("taxonomy", "year", { per_page: -1 });

  return { years };
})(Edit);
