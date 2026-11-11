/**
 * What the viewer may do with a circle, one flag per app/Policies/CirclePolicy.php ability (CircleController::show's
 * can).
 */
export interface CirclePermissions {
    view_channels: boolean;
    request_to_join: boolean;
    invite: boolean;
    update: boolean;
    delete: boolean;
}
