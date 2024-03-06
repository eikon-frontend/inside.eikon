import React, { useState } from 'react';

import { useBlockProps } from "@wordpress/block-editor";
import { useSelect, useDispatch, withSelect, withDispatch } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import "./editor.scss";

function Edit({ attributes, setAttributes, posts }) {
  const [selectedPosts, setSelectedPosts] = useState(attributes.selectedPosts || []);
  const { editPost } = useDispatch('core/editor');

  const postOptions = posts
    ? posts.map((post) => ({ value: post.id, label: post.title.rendered }))
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

  return (
    <div {...useBlockProps()}>
      <label>
        <select
          multiple
          value={selectedPosts.map((post) => post.id)}
          onChange={handleSelectChange}
          style={{ width: '100%' }}
        >
          {postOptions.map((option) => (
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

  return { posts };
})(Edit);
