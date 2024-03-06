import React, { useState, useEffect } from 'react';

import { useBlockProps } from "@wordpress/block-editor";
import { useSelect, useDispatch, withSelect, withDispatch } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import "./editor.scss";

function Edit({ attributes, setAttributes, posts, years }) {
  const [selectedPosts, setSelectedPosts] = useState(attributes.selectedPosts || []);
  const [selectedYear, setSelectedYear] = useState('');
  const { editPost } = useDispatch('core/editor');


  const postOptions = posts
    ? posts.map((post) => ({ value: post.id, label: post.title.rendered, yearId: post.acf.year }))
    : [];

  const handleSelectChange = (event) => {
    const selectedOptions = Array.from(event.target.options)
      .filter((option) => option.selected);

    const newSelectedPosts = [];
    for (const option of selectedOptions) {
      const post = posts.find((post) => post.id === Number(option.value));
      newSelectedPosts.push(post);
    }
    setSelectedPosts(newSelectedPosts);
    setAttributes({ selectedPosts: newSelectedPosts });
    editPost({ meta: { selectedPosts: newSelectedPosts } });
  };

  const handleYearChange = (event) => {
    setSelectedYear(event.target.value);
  };

  return (
    <div {...useBlockProps()}>
      <label>
        Year:
        <select value={selectedYear} onChange={handleYearChange}>
          <option value=""></option>
          {years && years.map((year) => (
            <option key={year.id} value={year.id}>{year.name}</option>
          ))}
        </select>
      </label>
      <label>
        <select
          multiple
          value={selectedPosts.map((post) => post.id)}
          onChange={handleSelectChange}
          style={{ width: '100%' }}
        >
          {postOptions
            .filter(option => selectedYear == "" || option.yearId == selectedYear)
            .map((option) => (
              <option
                key={option.value}
                value={option.value}
                dangerouslySetInnerHTML={{ __html: option.label }}
              />
            ))}
        </select>
      </label>
      {selectedPosts.map((post, index) => {
        return (
          <div key={index}>
            <h2 dangerouslySetInnerHTML={{ __html: post.title.rendered }} />
            <pre>{JSON.stringify(post.slug, null, 2)}</pre>
          </div>
        );
      })}
    </div>
  );
}

export default withSelect((select) => {
  const { getEntityRecords } = select("core");

  const posts = getEntityRecords("postType", "project", { per_page: -1 });
  const years = getEntityRecords("taxonomy", "year", { per_page: -1 });

  return { posts, years };
})(Edit);
