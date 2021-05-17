import LocalOfferIcon from "@material-ui/icons/LocalOffer";
import { BENCHMARK, BENCHMARK_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import TagPicker from "components/core-ui/tag-picker";
import { makeStyles } from "@material-ui/core/styles";
import Box from "@material-ui/core/Box";
import InputLabel from "@material-ui/core/InputLabel";
import Select from "@material-ui/core/Select";
import MenuItem from "@material-ui/core/MenuItem";
import FormControl from "@material-ui/core/FormControl";
import SettingsRow from "../../../components/SettingsRow";

const STEP_TYPE = "tag_applied";

const stepAtts = {
  ...BENCHMARK_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: BENCHMARK,

  name: "Tag Applied",

  icon: <LocalOfferIcon />,

  read: ({ data, meta, stats }) => {
    const tagIds = meta.tag_ids;
    const condition = meta.condition || "any";

    if (!tagIds || tagIds.length === 0) {
      return <>{"Tags are applied!"}</>;
    } else if (tagIds && tagIds.length === 1) {
      return <>{`${tagIds.length} tag is applied`}</>;
    } else {
      return (
        <>{`${condition === "all" ? "All" : "Any of"} ${
          tagIds.length
        } tags are applied`}</>
      );
    }
  },

  edit: ({ data, meta, updateSettings }) => {
    if (!meta) {
      meta = {
        tag_ids: [],
        condition: "",
      };
    }

    const handleTagsChosen = (tagIds) => {
      updateSettings({
        ...meta,
        tag_ids: tagIds,
      });
    };

    return (
      <>
        <SettingsRow>
          <FormControl variant="outlined">
            <InputLabel id="tag-requires-label">{"Requires"}</InputLabel>
            <Select
              labelId="tag-requires"
              id="demo-simple-select-outlined"
              value={meta.condition || "any"}
              onChange={(e) =>
                updateSettings({
                  ...meta,
                  condition: e.target.value,
                })
              }
              label="Requires"
            >
              <MenuItem value={"any"}>{"Any of the following tags"}</MenuItem>
              <MenuItem value={"all"}>{"All of the following tags"}</MenuItem>
            </Select>
          </FormControl>
        </SettingsRow>
        <SettingsRow>
          <TagPicker
            selected={meta.tag_ids || []}
            onChange={handleTagsChosen}
          />
        </SettingsRow>
      </>
    );
  },
};

registerStepType(STEP_TYPE, stepAtts);
