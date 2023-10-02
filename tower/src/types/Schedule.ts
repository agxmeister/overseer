import {Link} from "@/types/Link";

export type Schedule = {
    key: string,
    begin?: string,
    end?: string,
    links?: {inward?: Link[], outward?: Link[]},
}

export enum Mode {
    View = "view",
    Edit = "edit",
}
