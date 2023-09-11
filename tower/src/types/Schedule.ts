import {Link} from "@/types/Link";

export type Schedule = {
    key: string,
    estimatedBeginDate?: string,
    estimatedEndDate?: string,
    links?: {inward?: Link[], outward?: Link[]},
}

export enum Mode {
    View = "view",
    Edit = "edit",
}
