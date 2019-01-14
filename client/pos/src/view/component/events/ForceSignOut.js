import CoreContainer from "../../../framework/container/CoreContainer";
import CoreComponent from "../../../framework/component/CoreComponent";
import ContainerFactory from "../../../framework/factory/ContainerFactory";
import ComponentFactory from "../../../framework/factory/ComponentFactory";
import {listen} from "../../../event-bus";
import LocalStorageHelper from '../../../helper/LocalStorageHelper';
import QueryService from "../../../service/QueryService";
import LogoutPopupAction from "../../action/LogoutPopupAction";
import SignoutAction from "../../action/SignoutAction";

export class ForceSignOutComponent extends CoreComponent {
    static className = 'ForceSignOutComponent';

    /**
     * constructor
     * @param props
     */
    constructor(props) {
        super(props);
        // this.state = {};
        listen('check-force-sign-out', eventData => {
            let {data, responseCode} = eventData;
            if (data.code === 901 &&
                LocalStorageHelper.get(LocalStorageHelper.POS_ID)) {
                let queryService = QueryService.reset();
                queryService.setPageSize(500).setCurrentPage(1);
                this.props.getNewLocations(queryService);
            } else if (data.code === 900 &&
                LocalStorageHelper.get(LocalStorageHelper.SESSION)) {
                this.props.forceSignOut();
                this.props.finishLogoutRequesting(data);
            } else if ((responseCode === 401) && (data.code !== 903)) {
                this.props.authorize();
            }
        });
    }

    render() {
        return (null)
    }
}

/**
 * @type {ForceSignOutComponent}
 */
const component = ComponentFactory.get(ForceSignOutComponent);

class ForceSignOutContainer extends CoreContainer {
    static className = 'ForceSignOutContainer';

    /**
     * Map actions
     * @param dispatch
     */
    static mapDispatch(dispatch) {
        return {
            getNewLocations: (queryService) => dispatch(SignoutAction.getNewLocations(queryService)),
            forceSignOut: () => dispatch(LogoutPopupAction.forceSignOut()),
            finishLogoutRequesting: (data) => dispatch(LogoutPopupAction.finishLogoutRequesting(data)),
            authorize: () => dispatch(LogoutPopupAction.authorize()),
        }
    }
}

/**
 *
 * @type {ForceSignOutContainer}
 */
const container = ContainerFactory.get(ForceSignOutContainer);

export default container.withRouter(component);
