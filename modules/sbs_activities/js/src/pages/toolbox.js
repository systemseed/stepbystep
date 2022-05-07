import React from "react";
import PropTypes from "prop-types";

const ActivityCard = ({ activity }) => {
  if (activity.is_locked) {
    return (
      <div className="toolbox-activity locked">
        <i className="material-icons locked">lock</i>
        <i className="material-icons icon">{activity.icon}</i>

        <h2 className="title">{activity.title}</h2>
      </div>
    );
  }
  return (
    <a className="toolbox-activity" href={activity.url}>
      <i className="material-icons icon">{activity.icon}</i>

      <h2 className="title">{activity.title}</h2>
    </a>
  );
};

ActivityCard.propTypes = {
  activity: PropTypes.shape({
    is_locked: PropTypes.bool,
    icon: PropTypes.string,
    title: PropTypes.string,
    url: PropTypes.string,
  }).isRequired,
};

const ToolboxPage = ({ activities }) => (
  <>
    {activities.map((activity) => (
      <ActivityCard key={activity.id} activity={activity} />
    ))}
  </>
);

ToolboxPage.propTypes = {
  activities: PropTypes.arrayOf(ActivityCard.propTypes.activity),
};

export default ToolboxPage;
