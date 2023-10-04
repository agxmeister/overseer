import {Link} from "@/types/Link";

export type Issue = {
    key: string,
    summary?: string,
    begin?: string,
    end?: string,
    links?: {inward?: Link[], outward?: Link[]},
    corrected?: boolean,
}
