import {format} from "./date";
import {describe} from "node:test";
import {getColumnsTemplate} from "./grid";

describe('date.format', () => {
    it.each([
        '2023-07-20',
        '2023-07-02',
        '2023-11-11',
    ])('convert date to string', (date) => {
        expect(format(new Date(date))).toEqual(date);
    })
})

describe('grid.getColumnsTemplate', () => {
    it.each([
        {columns: [], template: 'auto'},
        {columns: ['one'], template: '[col-one-start] auto [col-one-end]'},
        {columns: ['one', 'two'], template: '[col-one-start] auto [col-one-end col-two-start] auto [col-two-end]'},
    ])('generate CSS grid-template-columns by given column names', ({columns, template}) => {
        expect(getColumnsTemplate(columns)).toEqual(template);
    })
})
