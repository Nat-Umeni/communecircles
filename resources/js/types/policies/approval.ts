/**
 * What the viewer may do with an approval, one flag per app/Policies/ApprovalPolicy.php ability (ApprovalResource's can).
 */
export interface ApprovalPermissions {
    decide: boolean;
    cancel: boolean;
}
