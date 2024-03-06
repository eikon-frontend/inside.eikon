import React, { useState, useEffect } from 'react';

import { useBlockProps } from "@wordpress/block-editor";
import { useSelect, useDispatch, withSelect, withDispatch } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import "./editor.scss";

function Edit({ attributes, setAttributes, posts, years }) {
  const { editPost } = useDispatch('core/editor');

  const [selectedPosts, setSelectedPosts] = useState(attributes.selectedPosts || []);
  const [selectedYear, setSelectedYear] = useState('');
  const [selectedOptions, setSelectedOptions] = useState(attributes.selectedOptions || []);

  const postOptions = posts
    ? posts.map((post) => ({ value: post.id, label: post.title.rendered, yearId: post.acf.year }))
    : [];

  const addProject = (value) => {
    const selectedOption = postOptions.find(option => option.value === value);
    if (selectedOption) {
      setSelectedOptions(prevOptions => [...prevOptions, selectedOption]);

      const post = posts.find((post) => post.id === Number(selectedOption.value));
      if (post) {
        setSelectedPosts(prevPosts => [...prevPosts, post]);
      }
    }
  };

  const removeProject = (postId) => {
    const newSelectedPosts = selectedPosts.filter(post => post.id !== postId);
    setSelectedPosts(newSelectedPosts);

    const newSelectedOptions = selectedOptions.filter(option => option.value !== postId);
    setSelectedOptions(newSelectedOptions);
  };

  const handleYearChange = (event) => {
    setSelectedYear(event.target.value);
  };

  useEffect(() => {
    setAttributes({ selectedPosts, selectedOptions });
    editPost({ meta: { selectedPosts } });
  }, [selectedPosts, selectedOptions]);

  return (
    <div {...useBlockProps()}>
      <div>
        <label>
          Year:
          <select value={selectedYear} onChange={handleYearChange}>
            <option value=""></option>
            {years && years.map((year) => (
              <option key={year.id} value={year.id}>{year.name}</option>
            ))}
          </select>
        </label>
      </div>
      <div style={{ display: 'flex', justifyContent: 'space-between', gap: '5px' }}>
        <div style={{ width: '50%' }}>
          <h2>Project</h2>
          <div className='list-projects list-projects-available'>
            {postOptions
              .filter(option => selectedYear == "" || option.yearId == selectedYear)
              .filter(option => !selectedOptions.map(post => post.value).includes(option.value))
              .map((option, index) => (
                <button
                  key={option.value}
                  value={option.value}
                  className='project'
                  onClick={() => addProject(option.value)}
                  dangerouslySetInnerHTML={{ __html: option.label }}
                />
              ))}
          </div>
        </div>
        <div style={{ width: '50%', overflow: 'scroll' }}>
          <h2>Selected project</h2>
          <div className='list-projects list-projects-selected'>
            {selectedPosts.map((post, index) => {
              return (
                <button
                  key={post.id}
                  onClick={() => removeProject(post.id)}
                  dangerouslySetInnerHTML={{ __html: post.title.rendered }}
                />
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}

export default withSelect((select) => {
  const { getEntityRecords } = select("core");

  const posts = getEntityRecords("postType", "project", { per_page: -1 });
  const years = getEntityRecords("taxonomy", "year", { per_page: -1 });

  return { posts, years };
})(Edit);
