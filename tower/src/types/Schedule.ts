import {Issue} from "@/types/Issue";
import {Link} from "@/types/Link";

export type Schedule = {
    issues: Issue[],
    criticalChain: string[],
    buffers: Issue[],
    links: Link[],
}

export enum Mode {
    View = "view",
    Edit = "edit",
}
