import {Link} from "@/types/Link";

export type Issue = {
    key: string,
    begin: string,
    end: string,
    summary: string,
    links: {inward: Link[], outward: Link[]},
    corrected?: boolean,
}
