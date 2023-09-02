import {Link} from "@/types/Link";

export type Issue = {
    key: string,
    estimatedBeginDate: string,
    estimatedEndDate: string,
    summary: string,
    links: {inward: Link[], outward: Link[]},
    corrected?: boolean,
}
