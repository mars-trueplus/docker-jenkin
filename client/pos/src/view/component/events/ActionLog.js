import CoreContainer from "../../../framework/container/CoreContainer";
import CoreComponent from "../../../framework/component/CoreComponent";
import ContainerFactory from "../../../framework/factory/ContainerFactory";
import ComponentFactory from "../../../framework/factory/ComponentFactory";
import {listen} from "../../../event-bus";
import OrderAction from "../../../view/action/OrderAction";
import CustomerAction from "../../../view/action/CustomerAction";
import OnHoldOrderAction from "../../../view/action/OnHoldOrderAction";

export class ActionLogComponent extends CoreComponent {
    static className = 'ActionLogComponent';

    constructor(props) {
        super(props);
        listen('order-update-data-finish', eventData => {
            this.props.orderUpdateDataFinish(eventData.result);
        });
        listen('save-customer', eventData => {
            this.props.saveCustomer(eventData.result);
        });
        listen('update-on-hold-order-finish', eventData => {
            this.props.updateOnHoldOrderFinish(eventData.result);
        });
    }

    render() {
        return (null)
    }
}

class ActionLogContainer extends CoreContainer {
    static className = 'ActionLogContainer';

    static mapDispatch(dispatch) {
        return {
            orderUpdateDataFinish: (result) => dispatch(
                OrderAction.syncActionUpdateDataFinish([result])
            ),
            saveCustomer: (result) => dispatch(
                CustomerAction.saveCustomer([result])
            ),
            updateOnHoldOrderFinish: (result) => dispatch(
                OnHoldOrderAction.syncActionUpdateOnHoldOrderFinish([result])
            ),
        }
    }
}

export default ContainerFactory.get(ActionLogContainer).withRouter(
    ComponentFactory.get(ActionLogComponent)
);
