import {Issue} from "@/types/Issue";

export type Schedule = {
    issues: Issue[],
}

export enum Mode {
    View = "view",
    Edit = "edit",
}
