import React, { useState, useEffect } from 'react';
import { useBlockProps } from '@wordpress/block-editor';
import { useDispatch, withSelect } from "@wordpress/data";
import { FormTokenField } from '@wordpress/components';
import { __ } from "@wordpress/i18n";
import "./editor.scss";

function Edit({ attributes, setAttributes, posts }) {
  const { editPost } = useDispatch('core/editor');

  const [selectedPosts, setSelectedPosts] = useState(attributes.selectedPosts || []);
  const [postSuggestions, setPostSuggestions] = useState([]);

  useEffect(() => {
    if (posts) {
      setPostSuggestions(posts.map((post) => post.title?.rendered));
    }
  }, [posts]);

  const addDepartment = (tokens) => {
    const selectedOption = posts.find(post => post.title?.rendered === tokens[tokens.length - 1]);
    if (selectedOption) {
      setSelectedPosts(prevPosts => [...prevPosts, selectedOption]);
    } else {
      const removedPostTitles = selectedPosts.map(post => post.title?.rendered).filter(title => !tokens.includes(title));
      if (removedPostTitles.length > 0) {
        const removedPost = selectedPosts.find(post => post.title?.rendered === removedPostTitles[0]);
        if (removedPost) {
          removeDepartment(removedPost.id);
        }
      }
    }
  };

  const removeDepartment = (postId) => {
    const newSelectedPosts = selectedPosts.filter(post => post.id !== postId);
    setSelectedPosts(newSelectedPosts);
  };

  useEffect(() => {
    setAttributes({ selectedPosts });
    editPost({ meta: { selectedPosts } });
  }, [selectedPosts]);

  return (
    <div {...useBlockProps()}>
      <div className='eikonblock-title'>eikonblock // teaser department</div>
      <div>
        <FormTokenField
          label="Select Departments:"
          value={selectedPosts.map(post => post.title?.rendered)}
          suggestions={postSuggestions}
          onChange={addDepartment}
        />
      </div>
    </div>
  );
}

export default withSelect((select) => {
  const { getEntityRecords } = select("core");

  const posts = getEntityRecords("postType", "department", { per_page: -1 });

  return { posts };
})(Edit);
