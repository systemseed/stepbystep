import { withStyles } from "@material-ui/core";
import AudioBase from "@anu/components/Audio/AudioBase";

// Change background color for the audio paragraph.
export default withStyles((theme) => {
  return {
    container: {
      backgroundColor: theme.palette.washed.blue,
    },
  };
})(AudioBase);
