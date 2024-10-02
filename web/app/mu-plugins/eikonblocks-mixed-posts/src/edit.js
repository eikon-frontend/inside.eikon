import { useState } from 'react';
import { withSelect } from '@wordpress/data';
import { useBlockProps, InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

function Edit(props) {
  const { attributes, setAttributes, years, subjects } = props;
  const { selectedYear, selectedSubject, backgroundColor, textColor } = attributes;

  const handleYearChange = (event) => {
    setAttributes({ selectedYear: event.target.value });
  };

  const handleSubjectChange = (event) => {
    setAttributes({ selectedSubject: event.target.value });
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
        <div>
          <label>
            Select Subject:
            <select value={selectedSubject} onChange={handleSubjectChange}>
              <option value="">Select Subject</option>
              {subjects && subjects.map((subject) => (
                <option key={subject.id} value={subject.slug}>
                  {subject.name}
                </option>
              ))}
            </select>
          </label>
        </div>
        <div className="mixed-posts" data-year={selectedYear} data-subject={selectedSubject}></div>
      </div>
    </>
  );
}

export default withSelect((select) => {
  const { getEntityRecords } = select("core");

  const years = getEntityRecords("taxonomy", "year", { per_page: -1 });
  const subjects = getEntityRecords("taxonomy", "subjects", { per_page: -1 });

  return { years, subjects };
})(Edit);
