import PropTypes from "prop-types";
import * as fields from "@anu/utilities/fields";

const transformStorylinesPage = ({ data }) => {
  return {
    storylines: data.storylines.map((item) => transformStoryline(item)),
    currentStoryline: data.currentStoryline,
  };
};

const transformStoryline = (storyline) => {
  return {
    id: fields.getTextValue(storyline, "tid"),
    title: fields.getTextValue(storyline, "name"),
    description: fields.getTextValue(storyline, "field_storyline_description"),
    image: fields.getImage(storyline, "field_storyline_image"),
  };
};

const storylinePropTypes = {
  id: PropTypes.string.isRequired,
  title: PropTypes.string.isRequired,
  description: PropTypes.string,
  image: PropTypes.shape({
    url: PropTypes.string.isRequired,
    alt: PropTypes.string.isRequired,
    type: PropTypes.string.isRequired,
  }),
};
const storylinesPagePropTypes = {
  storylines: PropTypes.arrayOf(PropTypes.shape(storylinePropTypes)),
  currentStoryline: PropTypes.string,
};

export { transformStorylinesPage, storylinesPagePropTypes, storylinePropTypes };
