import React from "react";
import PageTemplate from "@anu/pages/storylines/PageTemplate";
import { storylinesPagePropTypes } from "@anu/utilities/transform.storylines";

const StorylinesPage = ({ data }) => {
  return (
    <PageTemplate
      storylines={data.storylines}
      currentStoryline={data.currentStoryline}
    />
  );
};

StorylinesPage.propTypes = storylinesPagePropTypes;

export default StorylinesPage;
