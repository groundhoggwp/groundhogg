/**
 * External dependencies
 */
import {Component, Fragment} from '@wordpress/element';
import {compose} from '@wordpress/compose';
import {withSelect, withDispatch} from '@wordpress/data';
import {__} from '@wordpress/i18n';
import TextField from '@material-ui/core/TextField';
import {castArray} from 'lodash';

/**
 * Internal dependencies
 */
import {TAGS_STORE_NAME} from '../../../../data';
import {BROADCASTS_STORE_NAME} from '../../../../data';

class Dashboard extends Component {

    constructor() {
        super(...arguments);
        this.onSubmit = this.onSubmit.bind(this);
        this.setValue = this.setValue.bind(this);
        this.state = {value: ''};
    }

    setValue(event) {
        this.setState({
            value: event.target.value
        })
    }

    async onSubmit() {
        const {
            value
        } = this.state;

        const {
            updateTags
        } = this.props;

        updateTags({tags: value})
    }

    render() {

        const tags = castArray(this.props.tags.tags);
        const broadcasts = castArray(this.props.broadcasts.broadcasts);
        return (
            <div>
                <Fragment>
                    <h2>Dashboard</h2>
                    <ol>
                        {
                            tags.map((tag) => {
                                return (<li>{tag.tag_name}</li>)
                            })
                        }
                    </ol>
                    <TextField id="outlined-basic" label="Add Tags" variant="outlined" onKeyUp={this.setValue}/>
                    <p onClick={this.onSubmit}>Add</p>
                </Fragment>
                <Fragment>
                    <h2>BroadCast</h2>
                    <ol>
                        {
                            broadcasts.map((broadcast) => {
                                return (<li>{broadcast.title}</li>)
                            })
                        }
                    </ol>

                </Fragment>
            </div>
        );
    }
}

// default export
export default compose(
    withSelect((select) => {
        const {
            getTags,
            isTagsUpdating
        } = select(TAGS_STORE_NAME);

        const {
            getBroadcasts,
        } = select(BROADCASTS_STORE_NAME);

        const tags = getTags();
        const tagUpdate = isTagsUpdating();
        const broadcasts = getBroadcasts();
        return {
            tags,
            broadcasts,
            tagUpdate,
            // broadcastUpdate
        };
    }),
    withDispatch((dispatch) => {
        const {updateTags} = dispatch(TAGS_STORE_NAME);
        const {updateBroadcasts} = dispatch(BROADCASTS_STORE_NAME);
        return {
            updateTags,
            updateBroadcasts
        };
    })
)(Dashboard);
